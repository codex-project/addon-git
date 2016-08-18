<?php
namespace Codex\Addon\Git;

use Codex\Codex;
use Codex\Console\ListCommand;
use Codex\Projects\Project;
use Codex\Support\Traits\CodexProviderTrait;
use Laradic\ServiceProvider\ServiceProvider;

class GitServiceProvider extends ServiceProvider
{
    use CodexProviderTrait;

    protected $dir = __DIR__;

    protected $configFiles = [ 'codex-git' ];

    protected $commands = [
        Console\SyncCommand::class,
    ];

    protected $providers = [
        \Sebwite\Git\GitServiceProvider::class,
        Http\HttpServiceProvider::class,
    ];

    protected $bindings = [
        'codex.git.syncer' => Syncer::class
    ];

    public function register()
    {
        $app = parent::register();
        $this->codexIgnoreRoute('_git-webhook');
        Codex::extend('git', CodexGit::class);
        Project::extend('git', GitProject::class);

        return $app;
    }

    public function boot()
    {

        $app = parent::boot();
        $this->codexProjectConfig('codex-git.default-project-config');

        ListCommand::macro('listGitProjects', function () {
            /** @var ListCommand $this */
            collect(codex('projects')->all())->filter(function (Project $project) {
                return $project->config('git.enabled', false) === true;
            })->each(function (Project $project) {
                $this->line(" - {$project->getName()}");
            });
        });

        return $app;
    }

}
