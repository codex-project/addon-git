<?php
namespace Codex\Addon\Git;

use Closure;
use Codex\Core\Projects\Project;
use Codex\Core\Traits\HookableTrait;
use Illuminate\Contracts\Cache\Repository;
use Sebwite\Git\Contracts\Manager;
use Sebwite\Support\Str;
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

    /**
     * @var \Sebwite\Git\Contracts\Manager
     */
    protected $git;

    /**
     * @var \Codex\Core\Projects\Project
     */
    protected $project;

    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * @var \Codex\Core\Codex|\Codex\Core\Contracts\Codex
     */
    protected $codex;

    /**
     * Syncer constructor.
     *
     * @param \Codex\Core\Projects\Project           $project
     * @param \Sebwite\Git\Contracts\Manager         $git
     * @param \Illuminate\Contracts\Cache\Repository $cache
     */
    public function __construct(Project $project, Manager $git, Repository $cache)
    {
        $this->project = $project;
        $this->git     = $git;
        $this->cache   = $cache;
        $this->fs      = $project->getFiles();
        $this->codex   = $project->getCodex();

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
    protected function setting($key, $default = null)
    {
        return array_get($this->project->config('git', [ ]), $key, $default);
    }

    /**
     * client method
     *
     * @param null $connection
     *
     * @return \Sebwite\Git\Remotes\Remote
     */
    protected function client($connection = null)
    {
        $connection = $connection ?: $this->setting('connection');
        return $this->git->connection($connection);
    }

    /**
     * ensureDirectory method
     *
     * @param $path
     */
    protected function ensureDirectory($path)
    {

        if ( !$this->fs->exists($path) ) {
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
    protected function log($level, $message, $context = [ ])
    {
        $this->codex->log($level, $message, $context);
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
        $current  = 0;
        $branches = $this->getBranchesToSync();
        $versions = $this->getVersionsToSync();
        foreach ( $branches as $branch ) {
            $this->syncRef($branch, 'branch');
            $current++;
            $this->fire('tick', [ 'branch', $current, count($branches), $branches, $branch ]);
            #$tick($current, count($branches), $branches);
        }
        foreach ( $versions as $version ) {
            $this->syncRef($version, 'tag');
            $current++;
            $this->fire('tick', [ 'version', $current, count($versions), $versions, $version ]);
            #$tick($current, count($version), $version);
        }
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
        $owner     = $this->setting('owner');
        $repo      = $this->setting('repository');
        $docPath   = $this->setting('sync.paths.docs');
        $menuPath  = $this->setting('sync.paths.menu');
        $indexPath = $this->setting('sync.paths.index');

        $remote = $this->client($this->setting('connection'));
        $rfs    = $remote->getFilesystem($repo, $owner, $ref);

        $files = $rfs->allFiles($docPath);
        $total = count($files);


        if ( !$rfs->exists($indexPath) ) {
            return $this->log('error', 'syncRef could not find the index file', [ $indexPath ]);
        }

        if ( !$rfs->exists($menuPath) ) {
            return $this->log('error', 'syncRef could not find the menu file', [ $indexPath ]);
        }

        $destinationDir = $ref;
        $this->ensureDirectory($destinationDir);

        $syncer = $this;
        foreach ( $files as $current => $file ) {
            $localPath = path_relative($file, $docPath);

            if ( $progress instanceof Closure ) {
                $this->project->getContainer()->call($progress, compact('current', 'total', 'file', 'files', 'syncer'));
            }
            $localPath = path_join($destinationDir, $localPath);
            $dir       = path_get_directory($localPath);
            $this->ensureDirectory($dir);
            $rfs->exists($file) && $this->fs->put($localPath, $rfs->get($file));
            $this->fire('tick.file', [ $current, $total, $files, $file, $syncer ]);
        }

        // index.md resolving
        $indexFile = $rfs->get($indexPath);
        $this->fs->put(path_join($destinationDir, 'index.md'), $indexFile);


        if ( $type === 'branch' ) {
            $branch = $remote->getBranch($this->setting('repository'), $ref, $this->setting('owner'));
            $this->cache->forever(md5($this->project->getName() . $branch[ 'name' ]), $branch[ 'sha' ]);
        }
        $this->fire('finish', [ $ref, $type ]);
    }

    /**
     * getBranchesToSync method
     * @return array
     */
    public function getBranchesToSync()
    {
        $allowedBranches = $this->setting('sync.constraints.branches');
        if ( count($allowedBranches) === 0 ) {
            return [ ];
        }
        $this->fire('branches.start', [ $allowedBranches ]);

        $branchesToSync = [ ];
        $remote         = $this->client($this->setting('connection'));
        $repo           = $this->setting('repository');
        $owner          = $this->setting('owner');
        $branches       = $remote->getBranches($repo, $owner);

        foreach ( $branches as $branch => $sha ) {
            if ( !in_array('*', $allowedBranches, true) and !in_array($branch, $allowedBranches, true) ) {
                continue;
            }
            $cacheKey        = md5($this->project->getName() . $branch);
            $cached          = $this->cache->get($cacheKey, false);
            $destinationPath = path_join($this->project->getPath(), $branch);

            if ( $cached !== $sha || $cached === false || !$this->fs->exists($destinationPath) ) {
                $branchesToSync[] = $branch;
            }
        }
        $this->fire('branches.finish', [ $branchesToSync ]);
        return $branchesToSync;
    }

    /**
     * getVersionsToSync method
     * @return array
     */
    public function getVersionsToSync()
    {
        $versionsToSync      = [ ];
        $remote              = $this->client($this->setting('connection'));
        $currentVersions     = $this->project->getRefs();
        $allowedVersionRange = new expression($this->setting('sync.constraints.versions'));
        $tags                = $remote->getTags($this->setting('repository'), $this->setting('owner')); #$this->remote->repositories()->tags();
        $this->fire('versions.start', [ $tags ]);

        foreach ( $tags as $tag => $sha ) {
            try {
                $version = new version($tag);
            }
            catch (SemVerException $e) {
                continue;
            }
            if ( $version->satisfies($allowedVersionRange) === false or in_array($version->getVersion(), $currentVersions, true) ) {
                continue;
            }
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
     * @return Manager
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
     * @return \Codex\Core\Codex|\Codex\Core\Contracts\Codex
     */
    public function getCodex()
    {
        return $this->codex;
    }




}