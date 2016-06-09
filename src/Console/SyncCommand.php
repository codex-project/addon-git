<?php
/**
 * Part of Robin Radic's PHP packages.
 *
 * MIT License and copyright information bundled with this package
 * in the LICENSE file or visit http://radic.mit-license.org
 */
namespace Codex\Addon\Git\Console;

use Codex\Addon\Git\Syncer;

/**
 * This is the CoreListCommand class.
 *
 * @package                   Codex\Core
 * @version                   1.0.0
 * @author                    Robin Radic
 * @license                   MIT License
 * @copyright                 2015, Robin Radic
 * @link                      https://github.com/robinradic
 */
class SyncCommand extends Command
{
    protected $signature = 'codex:git:sync {name? : The name of the project}
                                          {--queue : Put the sync job on the queue}
                                          {--all : Sync all projects}';

    protected $description = 'Synchronise all projects that have the git addon enabled.';

    public function handle()
    {

        $projects = [ ];
        foreach ( $this->codex->projects->all() as $project ) {
            if ( $project->config('git', false) !== false ) {
                $projects[] = $project->getName();
            }
        }


        $this->startListeners();


        if ( $this->option('all') ) {
            foreach ( $projects as $project ) {
                $this->comment("Starting sync job for [{$project}]" . ($this->option('queue') ? ' and pushed it onto the queue.' : '. This might take a while.'));
                $this->sync($project, $this->option('queue'));
            }
        } else {
            $project = $this->argument('name') ? $this->argument('name') : $this->choice('Pick the git enabled project you wish to sync', $projects);
            $this->comment("Starting sync job for [{$project}]" . ($this->option('queue') ? ' and pushed it onto the queue.' : '. This might take a while.'));
            $this->sync($project, $this->option('queue'));
        }
    }

    protected function sync($project, $queue = false)
    {
        if ( $queue ) {
            $this->git->createSyncJob($project);
        } else {
            $this->git->getProjectSyncer($project)->syncAll();
        }
    }

    protected function startListeners()
    {
        $listeners = [ //->project->getName()
            'tick:file' => function(Syncer $syncer, $current, $total, $all, $now){
                $name = $syncer->getProject()->getName();
                $this->line("tick:file ($current/$total) [$name:$now]");
            },
            'tick'            => function (Syncer $syncer, $type, $current, $total, $all, $now) {
                $name = $syncer->getProject()->getName();
                $this->line("tick ($current/$total) [$name:$type:$now]");
            },
            'start'           => function (Syncer $syncer, $ref, $type) {
                $name = $syncer->getProject()->getName();
                $this->line("start ($type) [$name:$ref]");
            },
            'finish'          => function (Syncer $syncer, $ref, $type) {
                $name = $syncer->getProject()->getName();
                $this->line("finish ($type) [$name:$ref]");
            },
            'branches:start'  => function (Syncer $syncer, $branches) {
                $name = $syncer->getProject()->getName();
                $branches = implode(',', $branches);
                $this->line("branches:start [$name:$branches]");
            },
            'branches:finish' => function (Syncer $syncer, $branches) {
                $name = $syncer->getProject()->getName();
                $branches = implode(',', $branches);
                $this->line("branches:finish [$name:$branches]");
            },
            'versions:start'  => function (Syncer $syncer, $versions) {
                $name = $syncer->getProject()->getName();
                $versions = implode(',', $versions);
                $this->line("versions:start [$name:$versions]");
            },
            'versions:finish' => function (Syncer $syncer, $versions) {
                $name = $syncer->getProject()->getName();
                $versions = implode(',', $versions);
                $this->line("versions:finish [$name:$versions]");
            },
        ];

        foreach ( $listeners as $name => $listener ) {
            $this->getLaravel()->make('events')->listen("codex:git:syncer:{$name}", $listener);
        }
    }
}
