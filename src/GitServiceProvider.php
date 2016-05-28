<?php
namespace Codex\Addon\Defaults\Git;

use Codex\Core\Traits\CodexProviderTrait;
use Sebwite\Support\ServiceProvider;

class GitServiceProvider extends ServiceProvider
{
    use CodexProviderTrait;

    protected $scanDirs = true;

    protected $configFiles = ['codex-addon.git'];

    #protected $findCommands = [ 'Console' ];

    protected $commands = [
        Console\SyncCommand::class
    ];

    protected $providers = [
        \Sebwite\Git\GitServiceProvider::class,
        Http\HttpServiceProvider::class
    ];

    protected $shared = [
        'codex.git' => Git::class
    ];

    public function boot()
    {
        $app = parent::boot();
        $this->codexProjectConfig('codex-addon.git.default-project-config');
        $this->codexIgnoreRoute('_git-webhook');
        return $app;
    }

}