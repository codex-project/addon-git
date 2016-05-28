<?php
/**
 * Part of Robin Radic's PHP packages.
 *
 * MIT License and copyright information bundled with this package
 * in the LICENSE file or visit http://radic.mit-license.org
 */
namespace Codex\Addon\Defaults\Git\Console;

use Codex\Addon\Defaults\Git\Syncer;

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

    protected $description = 'Synchronise all Github projects.';

    public function handle()
    {

        $projects = [ ];
        foreach ( $this->codex->projects->all() as $project )
        {
            if ( $project->config('git', false) !== false )
            {
                $projects[] = $project->getName();
            }
        }

        if ( !$this->option('queue') )
        {
            $this->startListeners();
        }

        if ( $this->option('all') )
        {
            foreach ( $projects as $project )
            {
                $this->comment("Starting sync job for [{$project}]" . ($this->option('queue') ? ' and pushed it onto the queue.' : '. This might take a while.'));
                $this->sync($project, $this->option('queue'));
            }
        }
        else
        {
            $project = $this->argument('name') ? $this->argument('name') : $this->choice('Pick the git enabled project you wish to sync', $projects);
            $this->comment("Starting sync job for [{$project}]" . ($this->option('queue') ? ' and pushed it onto the queue.' : '. This might take a while.'));
            $this->sync($project, $this->option('queue'));
        }
    }

    protected function sync($project, $queue = false)
    {
        $output = $this->output;
        if ( $queue )
        {
            $this->git->createSyncJob($project);
        }
        else
        {
            $this->git->gitSyncer($project)->syncWithProgress(function ($current, $version, $versions) use ($output)
            {
                $output->writeln("Written brch");
            }, function ($current, $total, $file, $files, $syncer) use ($output)
            {
                $output->writeln("Written file [{$current}/{$total}] [{$file}]");
            });
        }
    }

    protected function startListeners()
    {
        $listeners = [
            'git.syncer.start'           => function ($name, $ref, $type)
            {

                $this->line("git.syncer.start ($type) [$name:$ref]");
            },
            'git.syncer.finish'          => function ($name, $ref, $type)
            {

                $this->line("git.syncer.finish ($type) [$name:$ref]");
            },
            'git.syncer.branches.start'  => function ($name, $branches)
            {

                $branches = implode(',', $branches);
                $this->line("git.syncer.branches.start [$name]");
            },
            'git.syncer.branches.finish' => function ($name, $branches)
            {

                $branches = implode(',', $branches);
                $this->line("git.syncer.branches.finish [$name:$branches]");
            },
            'git.syncer.versions.start'  => function ($name, $versions)
            {

                $versions = implode(',', $versions);
                $this->line("git.syncer.versions.start [$name:$versions]");
            },
            'git.syncer.versions.finish' => function ($name, $versions)
            {

                $versions = implode(',', $versions);
                $this->line("git.syncer.versions.finish [$name:$versions]");
            }
        ];

        foreach ( $listeners as $name => $listener )
        {
            $this->getLaravel()->make('events')->listen($name, $listener);
        }
    }
}
