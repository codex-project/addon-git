<?php
namespace Codex\Addon\Git;

use Codex\Console\ListCommand;
use Codex\Contracts\Codex;
use Codex\Projects\Project;
use Codex\Traits\CodexProviderTrait;
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

    public function register()
    {
        $app = parent::register();
        $this->codexIgnoreRoute('_git-webhook');
        $this->codexHook('constructed', function (Codex $codex){
            $codex->extend('git', CodexGit::class);
        });
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