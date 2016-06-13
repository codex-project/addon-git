<?php

namespace Codex\Addon\Git\Jobs;

use Codex\Support\QueuedJob;

/**
 * This is the class SyncProject.
 *
 * @package        Codex\Addon
 * @author         CLI
 * @copyright      Copyright (c) 2015, CLI. All rights reserved
 */
class SyncJob extends QueuedJob
{
    /** @var string */
    protected $project;

    /**
     * @param                          string $project The name of the project
     */
    public function __construct($project)
    {
        $this->project = $project;
    }

    public function handle()
    {
        
        $syncer = codex()->git->getProjectSyncer($this->project);
        $syncer->syncAll();
    }
}
