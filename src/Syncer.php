<?php
namespace Codex\Addon\Git;

use Closure;
use Codex\Exception\CodexException;
use Codex\Projects\Project;
use Codex\Support\Traits\HookableTrait;
use Illuminate\Contracts\Cache\Repository;
use Laradic\Support\Str;
use Laradic\Git\Contracts\Manager;
use vierbergenlars\SemVer\expression;
use vierbergenlars\SemVer\SemVerException;
use vierbergenlars\SemVer\version;

/**
 * This is the class Syncer.
 *
 * @package        Codex\Hooks
 * @author         Codex
 * @copyright      Copyright (c) 2015, Codex. All rights reserved
 */
class Syncer
{
    use HookableTrait;

    /**
     * @var \Illuminate\Contracts\Filesystem\Filesystem
     */
    protected $fs;

    /**
     * @var string
     */
    protected $remote;

    /** @var \Laradic\Git\Contracts\Manager|\Laradic\Git\Manager */
    protected $git;

    /** @var \Codex\Projects\Project */
    protected $project;

    /** @var \Illuminate\Contracts\Cache\Repository */
    protected $cache;

    /** @var \Codex\Codex */
    protected $codex;

    protected $connection;

    protected $connections = [ ];

    /**
     * Syncer constructor.
     *
     * @param \Codex\Projects\Project                             $project
     * @param \Laradic\Git\Contracts\Manager|\Laradic\Git\Manager $git
     * @param \Illuminate\Contracts\Cache\Repository              $cache
     */
    public function __construct(Project $project, Manager $git, Repository $cache)
    {
        $this->project     = $project;
        $this->git         = $git;
        $this->cache       = $cache;
        $this->fs          = $project->getFiles();
        $this->codex       = $project->getCodex();
        $this->connections = $project->config('git.connections', [ ]);
        $this->connection  = null;

        $this->hookPoint('git:syncer', [ $this ]);
    }

    /**
     * setting method
     *
     * @param      $key
     * @param null $default
     *
     * @return mixed
     */
    public function setting($key, $default = null)
    {

        if ( count($this->connections) === 0 )
        {
            throw CodexException::because('There are no git connections defined');
        }

        if ( $this->connection === null )
        {
            $this->connection = array_keys($this->connections)[0];
        }

        return array_get($this->project->config('git.connections.' . $this->connection, [ ]), $key, $default);
    }

    /**
     * @return null
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param null $connection
     *
     * @return Syncer
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;

        return $this;
    }



    /**
     * client method
     *
     * @param null $connection
     *
     * @return \Laradic\Git\Remotes\Remote
     */
    public function client($connection = null)
    {
        $connection = $connection ?: $this->setting('service');

        return $this->git->connection($connection);
    }

    /**
     * ensureDirectory method
     *
     * @param $path
     */
    protected function ensureDirectory($path)
    {

        if ( !$this->fs->exists($path) )
        {
            $this->fs->makeDirectory($path);
        }
    }

    /**
     * log method
     *
     * @param       $level
     * @param       $message
     * @param array $context
     */
    public function log($level, $message, $context = [ ])
    {
        $this->codex->log($level, $message, $context);

        return $message;
    }

    /**
     * fire method
     *
     * @param       $name
     * @param array $context
     */
    public function fire($name, array $context = [ ])
    {
        $name     = Str::ensureLeft($name, 'git.syncer.');
        $hookName = implode(':', explode('.', $name));
        //$context  = array_merge([ $this ], $context);

        $this->log('info', $name, $context);
        $this->hookPoint($hookName, $context);
    }

    /**
     * syncAll method
     */
    public function syncAll()
    {
        $this->fire('all');
        $current  = 0;
        $branches = $this->getBranchesToSync();
        $versions = $this->getVersionsToSync();
        foreach ( $branches as $branch )
        {
            $this->syncRef($branch, 'branch');
            $current++;
            $this->fire('tick', [ 'branch', $current, count($branches), $branch ]);
            #$tick($current, count($branches), $branches);
        }
        foreach ( $versions as $version )
        {

            $this->syncRef($version, 'tag');
            $current++;
            $this->fire('tick', [ 'version', $current, count($versions), $version ]);
            #$tick($current, count($version), $version);
        }
        $this->fire('all.done');
    }

    /**
     * syncRef method
     *
     * @param               $ref
     * @param               $type
     * @param \Closure|null $progress
     */
    public function syncRef($ref, $type, Closure $progress = null)
    {
        $this->fire('start', [ $ref, $type ]);
        $owner      = $this->setting('owner');
        $repo       = $this->setting('repository');
        $remote     = $this->client();
        $downloader = $this->setting('downloader');
        $gitConfig     = $this->git->getConfig($this->setting('service'));
        $driver     = $gitConfig[ 'driver' ];

        if ( $driver === 'github' )
        {
            // Downloading large files with the git downloader fails.
            // The Github api can get 1MB max blob size.
            // So for github drive, we hard define zip as downloader
//            $downloader = 'zip';
        }

        $this->createDownloader($downloader)->download($owner, $repo, $ref);

        if ( $type === 'branch' )
        {
            $branch = $remote->getBranch($this->setting('repository'), $ref, $this->setting('owner'));
            $this->cache->forever(md5($this->project->getName() . $branch[ 'name' ]), $branch[ 'sha' ]);
        }
        $this->fire('finish', [ $ref, $type ]);
    }

    /**
     * getBranchesToSync method
     *
     * @return array
     */
    public function getBranchesToSync()
    {
        $allowedBranches = $this->setting('sync.constraints.branches');
        if ( count($allowedBranches) === 0 )
        {
            return [ ];
        }
        $this->fire('branches.start', [ $allowedBranches ]);

        $branchesToSync = [ ];
        $remote         = $this->client();
        $repo           = $this->setting('repository');
        $owner          = $this->setting('owner');
        $branches       = $remote->getBranches($repo, $owner);

        foreach ( $branches as $branch => $sha )
        {
            if ( !in_array('*', $allowedBranches, true) && !in_array($branch, $allowedBranches, true) )
            {
                continue;
            }
            $cacheKey        = md5($this->project->getName() . $branch);
            $cached          = $this->cache->get($cacheKey, false);
            $destinationPath = path_join($this->project->getPath(), $branch);

            if ( $cached !== $sha || $cached === false || !$this->fs->exists($destinationPath) )
            {
                $branchesToSync[] = $branch;
            }
        }
        $this->fire('branches.finish', [ $branchesToSync ]);

        return $branchesToSync;
    }

    /**
     * getVersionsToSync method
     *
     * @return array
     */
    public function getVersionsToSync()
    {
        $versionsToSync      = [ ];
        $remote              = $this->client($this->setting('connection'));
        $currentVersions     = $this->project->refs->all();
        $allowedVersionRange = new expression($this->setting('sync.constraints.versions'));
        $tags                = $remote->getTags($this->setting('repository'), $this->setting('owner'));
        $skipPatch           = $this->setting('sync.constraints.skip_patch_versions', false);
        $skipMinor           = $this->setting('sync.constraints.skip_minor_versions', false);
        $skipPatch           = $skipMinor === true ? true : $skipPatch; // if we skip minors, we automaticly skip patches as well

        $this->fire('versions.start', [ $tags ]);

        foreach ( $tags as $tag => $sha )
        {
            try
            {
                $version = new version($tag);
            }
            catch (SemVerException $e)
            {
                continue;
            }

            // Check all version constraints
            if ( $version->satisfies($allowedVersionRange) === false || in_array($version->getVersion(), $currentVersions, true) )
            {
                continue;
            }
            if ( $skipPatch === true && $version->getPatch() !== 0 )
            {
                continue;
            }
            if ( $skipMinor === true && $version->getMinor() !== 0 )
            {
                continue;
            }

            // This version is inside constraints, add it
            $versionsToSync[] = $version;
        }

        $this->fire('versions.finish', [ $versionsToSync ]);

        return $versionsToSync;
    }

    /**
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    public function getFs()
    {
        return $this->fs;
    }

    /**
     * Set the fs value
     *
     * @param \Illuminate\Contracts\Filesystem\Filesystem $fs
     */
    public function setFs($fs)
    {
        $this->fs = $fs;
    }

    /**
     * @return string
     */
    public function getRemote()
    {
        return $this->remote;
    }

    /**
     * Set the remote value
     *
     * @param string $remote
     */
    public function setRemote($remote)
    {
        $this->remote = $remote;
    }

    /**
     * @return \Laradic\Git\Manager
     */
    public function getGit()
    {
        return $this->git;
    }

    /**
     * Set the git value
     *
     * @param Manager $git
     */
    public function setGit($git)
    {
        $this->git = $git;
    }

    /**
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * Set the project value
     *
     * @param Project $project
     */
    public function setProject($project)
    {
        $this->project = $project;
    }

    /**
     * @return Repository
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Set the cache value
     *
     * @param Repository $cache
     */
    public function setCache($cache)
    {
        $this->cache = $cache;
    }

    /**
     * @return \Codex\Codex
     */
    public function getCodex()
    {
        return $this->codex;
    }

    /**
     * @param $name
     *
     * @return Codex\Addon\Git\Remote\Downloader\DownloadInterface|Codex\Addon\Git\Remote\Downloader\AbstractDownloader|Codex\Addon\Git\Remote\Downloader\GitDownloader
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function createDownloader($name)
    {
        $class = ucfirst($name) . 'Downloader';
        $class = 'Codex\\Addon\\Git\\Connection\\Downloader\\' . $class;

        return app()->build($class, [ $this ]);
    }

}
