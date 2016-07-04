<?php
/**
 * Part of the Codex Project packages.
 *
 * License and copyright information bundled with this package in the LICENSE file.
 *
 * @author Robin Radic
 * @copyright Copyright 2016 (c) Codex Project
 * @license http://codex-project.ninja/license The MIT License
 */

/**
 * Created by IntelliJ IDEA.
 * User: radic
 * Date: 7/4/16
 * Time: 6:35 AM
 */

namespace Codex\Addon\Git\Connection;


use Codex\Addon\Git\GitProject;

class Remote
{
    protected $client;

    protected $rfs;

    protected $git;

    protected $project;

    /** @var Connection */
    protected $connection;

    /**
     * Remote constructor.
     *
     * @param                                        $client
     * @param                                        $rfs
     * @param                                        $git
     * @param                                        $project
     * @param \Codex\Addon\Git\Connection\Connection $connection
     */
    public function __construct(GitProject $project, \Codex\Addon\Git\Connection\Connection $connection)
    {
        $this->client     = $client;
        $this->rfs        = $rfs;
        $this->git        = $git;
        $this->project    = $project;
        $this->connection = $connection;
    }


    public function getBranches()
    {
        $this->connection->getBranches();

        $branches       = $remote->getBranches(
            $this->connection->getRepository(),
            $this->connection->getOwner()
        );

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
    }
}
