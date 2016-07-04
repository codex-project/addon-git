<?php
/**
 * Part of Robin Radic's PHP packages.
 *
 * MIT License and copyright information bundled with this package
 * in the LICENSE file or visit http://radic.mit-license.org
 */
namespace Codex\Addon\Git\Console;

use Codex\Addon\Git\Jobs\SyncJob;
use Codex\Addon\Git\Syncer;
use Codex\Projects\Project;
use Illuminate\Foundation\Bus\DispatchesJobs;

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
    use DispatchesJobs;

    protected $signature = 'codex:git:sync
                                          {name? : The name of the project}
                                          {connection? : The connection to sync}
                                          {--queue : Put the sync job on the queue}
                                          {--all : Sync all projects}';

    protected $description = 'Synchronise all projects that have the git addon enabled.';

    public function handle()
    {
        $project = $this->codex->projects->get($name = $this->argument('name'));
        if(false === $project->git->isEnabled()){
            return $this->error('Not a git enabled project');
        }
        $project->git->sync($this->argument('connection'));
    }
    public function handle2()
    {
        $projects = $this->codex->git->getEnabledProjects();
        $this->startListeners();
        $queue = $this->option('queue');

        if ( $this->option('all') )
        {
            foreach ( $projects as $project )
            {
                $this->comment("Starting sync job for [{$project->getName()}]" . ($queue ? ' and pushed it onto the queue.' : '. This might take a while.'));
                $this->sync($project, $queue);
            }
        }
        else
        {
            // resolve project
            $project     = $this->argument('name') ? $this->argument('name') : $this->choice('Pick the git enabled project you wish to sync', $projects);
            $project     = $this->codex->projects->get($project);

            $git = $project->git;



            $connections = array_keys($project->config('git.connections'));
            $connection  = $this->argument('connection');
            if ( $connection === null && $queue !== true )
            {
                if ( count($connections) === 1 )
                {
                    $connection = $connections[ 0 ];
                }
                else
                {
                    $connections[] = 'all';
                    $connection = $this->choice('What connection?', $connections, $connections[ 0 ]);
                }
            }


            $this->comment("Starting sync job for [{$project}]" . ($queue ? ' and pushed it onto the queue.' : '. This might take a while.'));
            $this->sync($project, $queue, $connection);
        }
    }

    protected function sync($project, $queue = false, $connection = null)
    {
        if ( $project instanceof Project )
        {
            $project = $project->getName();
        }
        if ( $queue === true )
        {
            $this->dispatch(new SyncJob($project, $connection));
        }
        else
        {
            $syncer = $this->git->getProjectSyncer($project);
            $syncer->setConnection($connection);
            $syncer->syncAll();
        }
    }

    protected function startListeners()
    {
        $listeners = [ //->project->getName()
                       'tick:file'       => function (Syncer $syncer, $current, $total, $now)
                       {
                           $name = $syncer->getProject()->getName();
                           $this->line("tick:file ($current/$total) [$name:$now]");
                       },
                       'tick'            => function (Syncer $syncer, $type, $current, $total, $now)
                       {
                           $name = $syncer->getProject()->getName();
                           $this->line("tick ($current/$total) [$name:$type:$now]");
                       },
                       'start'           => function (Syncer $syncer, $ref, $type)
                       {
                           $name = $syncer->getProject()->getName();
                           $this->line("start ($type) [$name:$ref]");
                       },
                       'finish'          => function (Syncer $syncer, $ref, $type)
                       {
                           $name = $syncer->getProject()->getName();
                           $this->line("finish ($type) [$name:$ref]");
                       },
                       'branches:start'  => function (Syncer $syncer, $branches)
                       {
                           $name     = $syncer->getProject()->getName();
                           $branches = implode(',', $branches);
                           $this->line("branches:start [$name:$branches]");
                       },
                       'branches:finish' => function (Syncer $syncer, $branches)
                       {
                           $name     = $syncer->getProject()->getName();
                           $branches = implode(',', $branches);
                           $this->line("branches:finish [$name:$branches]");
                       },
                       'versions:start'  => function (Syncer $syncer, $versions)
                       {
                           $name     = $syncer->getProject()->getName();
                           $versions = implode(',', $versions);
                           $this->line("versions:start [$name:$versions]");
                       },
                       'versions:finish' => function (Syncer $syncer, $versions)
                       {
                           $name     = $syncer->getProject()->getName();
                           $versions = implode(',', $versions);
                           $this->line("versions:finish [$name:$versions]");
                       },
        ];

        foreach ( $listeners as $name => $listener )
        {
            $this->getLaravel()->make('events')->listen("codex:git:syncer:{$name}", $listener);
        }
    }
}
