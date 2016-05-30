<?php
namespace Codex\Addon\Git;

use Codex\Core\Console\ListCommand;
use Codex\Core\Projects\Project;
use Codex\Core\Traits\CodexProviderTrait;
use Sebwite\Support\ServiceProvider;

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

    protected $shared = [
        'codex.git' => Git::class,
    ];

    public function boot()
    {
        $app = parent::boot();
        $this->codexProjectConfig('codex-git.default-project-config');
        $this->codexIgnoreRoute('_git-webhook');

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