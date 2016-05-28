<?php
namespace Codex\Addon\Defaults\Git;

use Closure;
use Codex\Core\Projects\Project;
use Codex\Core\Traits\HookableTrait;
use Illuminate\Contracts\Cache\Repository;
use Sebwite\Git\Contracts\Manager;
use Sebwite\Support\Path;
use Symfony\Component\Yaml\Yaml;
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
     * @var \Sebwite\Git\Remotes\Manager
     */
    protected $git;

    /**
     * @var \Codex\Core\Project
     */
    protected $project;

    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /** @var \Codex\Core\Codex|\Codex\Core\Contracts\Codex */
    protected $codex;

    /**
     * Syncer constructor.
     *
     * @param \Codex\Core\Project                    $project
     * @param \Sebwite\Git\Manager                   $git
     * @param \Illuminate\Contracts\Cache\Repository $cache
     * @param \Illuminate\Events\Dispatcher          $dispatcher
     */
    public function __construct(Project $project, Manager $git, Repository $cache)
    {
        $this->project = $project;
        $this->git     = $git;
        $this->cache   = $cache;
        $this->fs      = $project->getFiles();
        $this->codex   = $project->getCodex();
        $this->remote  = 'github';

        $this->hookPoint('git:syncer', [ $this ]);
    }

    public function syncWithProgress(Closure $tick, Closure $subtick = null)
    {
        $current  = 0;
        $branches = $this->getBranchesToSync();
        $versions = $this->getVersionsToSync();
        foreach ( $branches as $branch ) {
            $this->syncRef($branch, 'branch', $subtick);
            $current++;
            $tick($current, count($branches), $branches);
        }
        foreach ( $versions as $version ) {
            $this->syncRef($version, 'tag', $subtick);
            $current++;
            $tick($current, count($version), $version);
        }
    }

    public function syncAll()
    {
        $this->syncBranches();
        $this->syncVersions();
    }

    public function syncBranches()
    {
        foreach ( $this->getBranchesToSync() as $branch ) {
            $this->syncRef($branch, 'branch');
        }
    }

    public function syncVersions()
    {
        foreach ( $this->getVersionsToSync() as $version ) {
            $this->syncRef($version, 'tag');
        }
    }

    protected function log($level, $message, $context = [ ])
    {
        $this->codex->log($level, $message, $context);
    }

    public function fire($name, array $context = [ ])
    {
        $hookName = implode(':', explode('.', $name));
        $context  = array_merge([ $this->project->getName() ], $context);

        $this->log('info', $name, $context);
        $this->hookPoint($hookName, $context);
    }

    public function syncRef($ref, $type, Closure $progress = null)
    {
        $this->fire('git.syncer.start', [ $ref, $type ]);
        $owner     = $this->setting('owner');
        $repo      = $this->setting('repository');
        $docPath   = $this->setting('sync.paths.docs');
        $menuPath  = $this->setting('sync.paths.menu');
        $indexPath = $this->setting('sync.paths.index');

        $remote = $this->client($this->setting('remote'));
        $rfs    = $remote->getFilesystem($repo, $owner, $ref);

        $files = $rfs->allFiles($this->setting('sync.paths.docs'));
        $total  = count($files);


        if ( !$rfs->exists($indexPath) ) {
            return $this->log('error', 'syncRef could not find the index file', [ $indexPath ]);
        }

        if ( !$rfs->exists($menuPath) ) {
            return $this->log('error', 'syncRef could not find the menu file', [ $indexPath ]);
        }

        $destinationDir  = $ref; //$this->project->refPath();
        $menuContent     = $rfs->get($this->setting('sync.paths.menu')); //#base64_decode($menu[ 'content' ]);
        $menuArray       = Yaml::parse($menuContent);
        $unfilteredPages = [ ];
        $this->extractDocumentsFromMenu($menuArray[ 'menu' ], $unfilteredPages);


        $this->ensureDirectory($destinationDir);
        #$files  = $rfs->allFiles($this->setting('sync.paths.docs'));
        $syncer = $this;

        foreach ( $files as $current => $file ) {
            $localPath = path_relative($file, $this->setting('sync.paths.docs'));

            if ( $progress instanceof Closure ) {
                $this->project->getContainer()->call($progress, compact('current', 'total', 'file', 'files', 'syncer'));
            }
            $localPath = path_join($destinationDir, $localPath);
            $dir       = path_get_directory($localPath);
            $this->ensureDirectory($dir);
            $this->fs->put($localPath, $rfs->get($file));
        }

        // index.md resolving
        $indexFile = $rfs->get($this->setting('sync.paths.index'));
        $this->fs->put(path_join($destinationDir, 'index.md'), $indexFile);


        if ( $type === 'branch' ) {
            $branch = $remote->getBranch($this->setting('repository'), $ref, $this->setting('owner'));
            $this->cache->forever(md5($this->project->getName() . $branch[ 'name' ]), $branch[ 'sha' ]);
        }
        $this->fire('git.syncer.finish', [ $ref, $type ]);
    }

    public function getBranchesToSync()
    {
        $allowedBranches = $this->setting('sync.constraints.branches');
        if ( count($allowedBranches) === 0 ) {
            return [ ];
        }
        $this->fire('git.syncer.branches.start', [ $allowedBranches ]);

        $branchesToSync = [ ];
        $remote         = $this->client($this->setting('remote'));
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
        $this->fire('git.syncer.branches.finish', [ $branchesToSync ]);
        return $branchesToSync;
    }

    public function getVersionsToSync()
    {
        $versionsToSync      = [ ];
        $remote              = $this->client($this->setting('remote'));
        $currentVersions     = $this->project->getRefs();
        $allowedVersionRange = new expression($this->setting('sync.constraints.versions'));
        $tags                = $remote->getTags($this->setting('repository'), $this->setting('owner')); #$this->remote->repositories()->tags();
        $this->fire('git.syncer.versions.start', [ $tags ]);

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

        $this->fire('git.syncer.versions.finish', [ $versionsToSync ]);
        return $versionsToSync;
    }

    public function extractDocumentsFromMenu($menuArray, &$documents = [ ])
    {
        foreach ( $menuArray as $key => $val ) {
            if ( is_string($key) && is_string($val) ) {
                $documents[] = $val;
            } elseif ( is_string($key) && $key === 'children' && is_array($val) ) {
                $this->extractDocumentsFromMenu($val, $documents);
            } elseif ( isset($val[ 'name' ]) ) {
                if ( isset($val[ 'document' ]) ) {
                    $documents[] = $val[ 'document' ];
                }
                if ( isset($val[ 'href' ]) ) {
                    //$item['href'] = $this->resolveLink($val['href']);
                }
                if ( isset($val[ 'icon' ]) ) {
                    //$item['icon'] = $val['icon'];
                }
                if ( isset($val[ 'children' ]) && is_array($val[ 'children' ]) ) {
                    $this->extractDocumentsFromMenu($val[ 'children' ], $documents);
                }
            }
        }
    }


    protected function setting($key, $default = null)
    {
        return array_get($this->project->config('git', []), $key, $default);
    }

    /**
     * client method
     *
     * @param null $remote
     *
     * @return \Sebwite\Git\Remotes\Remote
     */
    protected function client($remote = null)
    {
        $remote = isset($remote) ? $remote : $this->remote;
        $c      = [
            'credentials' => config('codex.hooks.git.credentials.' . $remote),
        ];

        return $this->git->connection($remote);
    }

    protected function ensureDirectory($path)
    {

        if ( !$this->fs->exists($path) ) {
            $this->fs->makeDirectory($path);
        }
    }
}