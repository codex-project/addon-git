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

use Codex\Addons\Annotations\Plugin;
use Codex\Addons\BasePlugin;
use Codex\Console\ListCommand;
use Codex\Projects\Project;

/**
 * This is the class GitPlugin.
 *
 * @package        Codex\Addon
 * @author         Radic
 * @copyright      Copyright (c) 2015, Radic. All rights reserved
 *
 * @Plugin("git")
 */
class GitPlugin extends BasePlugin
{

    protected $project= 'codex-git.default-project-config';

    protected $configFiles = [ 'codex-git' ];

    protected $commands = [
        Console\SyncCommand::class,
    ];

    protected $providers = [
        \Laradic\Git\GitServiceProvider::class
    ];

    protected $bindings = [
        'codex.git.syncer'  => Syncer::class,
        'codex.git'         => CodexGit::class,
        'codex.git.project' => GitProject::class
    ];

    protected $extend = [
        'codex'         => [ 'git' => 'codex.git' ],
        'codex.project' => [ 'git' => 'codex.git.project' ]
    ];

    public function register()
    {
        $app = parent::register();
        if ( $app[ 'config' ]->get('codex.http.enabled', false) === true ) {
            $app->register(Http\HttpServiceProvider::class);
        }

        $this->excludeRoute('_git-webhook');

        return $app;
    }

    public function boot()
    {

        $app = parent::boot();

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
