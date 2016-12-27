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
namespace Codex\Addon\Git;

use Codex\Codex;
use Codex\Projects\Project;
use Codex\Support\Traits\HookableTrait;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Laradic\Git\Contracts\Manager;

/**
 * This is the class Factory.
 *
 * @package        Codex\Hooks
 * @author         Codex
 * @copyright      Copyright (c) 2015, Codex. All rights reserved
 */
class CodexGit
{
    use HookableTrait,
        DispatchesJobs;

    /**
     * @var \Illuminate\Contracts\Filesystem\Filesystem
     */
    protected $files;

    /**
     * @var \Codex\Codex
     */
    protected $codex;

    /**
     * @var \Illuminate\Contracts\Queue\Queue
     */
    protected $queue;

    /**
     * @var \Laradic\Git\Contracts\Manager|\Laradic\Git\Manager
     */
    protected $git;

    /**
     * Factory constructor.
     *
     * @param \Codex\Codex                                        $parent
     * @param \Illuminate\Contracts\Filesystem\Filesystem         $files
     * @param \Illuminate\Contracts\Queue\Queue                   $queue
     * @param \Laradic\Git\Contracts\Manager|\Laradic\Git\Manager $git
     */
    public function __construct(Codex $parent, Filesystem $files, Queue $queue, Manager $git)
    {
        $this->codex = $parent;
        $this->files = $files;
        $this->queue = $queue;
        $this->git   = $git;

        $this->hookPoint('git:factory:done');
    }

    /**
     * @param $project
     *
     * @return \Codex\Addon\Git\Syncer
     * @throws \Codex\Exception\CodexException
     */
    public function getProjectSyncer($project)
    {
        if ( !$project instanceof Project )
        {
            $project = $this->codex->projects->get($project);
        }
        $syncer = app()->make('codex.git.syncer', [
            'project' => $project,
        ]);

        return $syncer;
    }

    /**
     * Get all projects that have the git addon enabled.
     *
     * @return Project[]
     */
    public function getEnabledProjects()
    {
        return array_filter($this->codex->projects->all(), function (Project $project)
        {
            return $project->config('git.enabled', false) === true;
        });
    }


    /**
     * get fsm value
     *
     * @return Filesystem
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * Set the fsm value
     *
     * @param Filesystem $files
     *
     * @return Factory
     */
    public function setFiles($files)
    {
        $this->files = $files;

        return $this;
    }

    /**
     * get codex value
     *
     * @return Codex
     */
    public function getCodex()
    {
        return $this->codex;
    }

    /**
     * Set the codex value
     *
     * @param Codex $codex
     *
     * @return Factory
     */
    public function setCodex($codex)
    {
        $this->codex = $codex;

        return $this;
    }

    /**
     * get queue value
     *
     * @return Queue
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * Set the queue value
     *
     * @param Queue $queue
     *
     * @return Factory
     */
    public function setQueue($queue)
    {
        $this->queue = $queue;

        return $this;
    }

    /**
     * get git value
     *
     * @return Manager|\Laradic\Git\Remotes\Manager
     */
    public function getGit()
    {
        return $this->git;
    }

    /**
     * Set the git value
     *
     * @param Manager|\Laradic\Git\Remotes\Manager $git
     *
     * @return Factory
     */
    public function setGit($git)
    {
        $this->git = $git;

        return $this;
    }
}
