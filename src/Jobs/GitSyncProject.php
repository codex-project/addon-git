<?php
/**
 * Part of the Codex Project packages.
 *
 * License and copyright information bundled with this package in the LICENSE file.
 *
 * @author    Robin Radic
 * @copyright Copyright 2016 (c) Codex Project
 * @license   http://codex-project.ninja/license The MIT License
 */
namespace Codex\Addon\Git\Jobs;

use Codex\Addon\Git\Connection\Connection;
use Codex\Addon\Git\Exceptions\CodexGitException;
use Codex\Addon\Git\GitProject;
use Codex\Support\Job;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class GitSyncProject extends Job implements ShouldQueue
{
    use InteractsWithQueue; //, SerializesModels;

    protected $gitConnection;

    /** @var GitProject */
    protected $git;


    protected $project;

    /**
     * @var \Codex\Codex
     */
    protected $codex;

    /**
     * Create a new job instance.
     *
     * @param \Codex\Addon\Git\GitProject $gitProject
     * @param null                        $gitConnection
     */
    public function __construct(GitProject $gitProject, $gitConnection = null)
    {
        $this->git           = $gitProject;
        $this->gitConnection = $gitConnection;
        $this->project       = $gitProject->getProject();
        $this->codex = $this->project->getCodex();
    }

    /**
     * Execute the job.
     *
     * @param \Laradic\Git\Manager                   $git
     * @param \Illuminate\Contracts\Cache\Repository $cache
     *
     * @internal param \Codex\Projects\Project $project
     * @internal param $connection
     */
    public function handle()
    {
        if ( false === $this->git->isEnabled() )
        {
            throw CodexGitException::notEnabled("for project [{$this->git->getProject()}]");
        }
        foreach($this->getGitConnections() as $con){
            $branches = $this->getBranchesToSync($con);
            $con->client()->getBranches($con->getRepository(), $con->getOwner());
            $con->getDownloader()->download($con->getOwner(), $con->getRepository(), 'master');
        }

    }

    /**
     * @return Connection[]|array
     */
    protected function getGitConnections()
    {

        if ( $this->gitConnection )
        {
            return [ $this->git->getConnection($this->gitConnection) ];
        }

        return collect($this->git->getConnections())->transform(function($name){
            return $this->git->getConnection($name);
        })->toArray();
    }


    /**
     * getBranchesToSync method
     *
     * @return array
     */
    protected function getBranchesToSync(Connection $con)
    {
        $allowedBranches = $con->getBranches();
        if ( count($allowedBranches) === 0 )
        {
            return [ ];
        }
        $branchesToSync = [ ];
        $remote         = $con->client();
        $branches       = $remote->getBranches($con->getRepository(), $con->getOwner());

        foreach ( $branches as $branch => $sha )
        {
            if ( !in_array('*', $allowedBranches, true) && !in_array($branch, $allowedBranches, true) )
            {
                continue;
            }
            $cacheKey        = md5("{$this->project}{$branch}");
            $cached          = $this->codex->getCache()->get($cacheKey, false);
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
        $currentVersions     = $this->project->getRefs();
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
}
