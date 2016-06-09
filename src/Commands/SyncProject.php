<?php

namespace Codex\Addon\Git\Commands;

use Codex\Contracts\Codex;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * This is the class SyncProject.
 *
 * @package        Codex\Addon
 * @author         CLI
 * @copyright      Copyright (c) 2015, CLI. All rights reserved
 */
class SyncProject implements ShouldQueue
{

    use InteractsWithQueue;

    /** @var string */
    protected $project;

    /**
     * @param                          string     $project The name of the project
     * @param \Codex\Contracts\Codex|\Codex\Codex $codex   The codex instance
     */
    public function __construct($project)
    {
        $this->project = $project;

    }

    public function handle()
    {
        codex()->log('alert', 'codex.hooks.git.sync.project.command', [
            'jobName'     => $this->job->getName(),
            'jobAttempts' => $this->attempts(),
            'project'     => $this->project,
        ]);


        codex()->


        app('codex.git')->gitSyncer($this->project)->syncAll();
    }
}
