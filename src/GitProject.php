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

/**
 * Created by IntelliJ IDEA.
 * User: radic
 * Date: 7/4/16
 * Time: 3:31 AM
 */

namespace Codex\Addon\Git;


use Codex\Addon\Git\Connection\Connection;
use Codex\Addon\Git\Jobs\GitSyncProject;
use Codex\Projects\Project;
use Codex\Support\Extendable;
use Codex\Support\Traits\ConfigTrait;
use Illuminate\Foundation\Bus\DispatchesJobs;

class GitProject extends Extendable
{
    use ConfigTrait,
        DispatchesJobs;

    /** @var \Codex\Projects\Project */
    protected $project;

    protected $connections = [ ];

    public function __construct(Project $parent)
    {
        $this->project = $parent;
        $this->setCodex($parent->getCodex());
        $this->setConfig($parent->config('git'));

        if ( $this->isEnabled() === false )
        {
            return;
        }

        foreach ( $parent->config('git.connections', [ ]) as $name => $config )
        {
            $this->createConnection($name, $config);
        }
    }

    public function getConnections()
    {
        return array_keys($this->config('connections', [ ]));
    }

    public function hasConnection($name)
    {
        return in_array($name, $this->getConnections(), true);
    }

    public function getConnection($name)
    {
        if ( !array_key_exists($name, $this->connections) )
        {
            $this->createConnection($name);
        }

        return $this->connections[ $name ];
    }

    protected function createConnection($name, array $config = null)
    {
        $connection = app()->build(Connection::class, ['name' => $name, 'gitProject' => $this]);
        $connection->hydrate($config ?: $this->config('connections.' . $name));

        return $this->connections[ $name ] = $connection;
    }

    /**
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }

    public function isEnabled()
    {
        return $this->config('enabled', false) === true;
    }

    public function sync($connection = null)
    {
        $job = app()->build(GitSyncProject::class, ['gitProject' => $this, '$connection' => $connection]);

        $this->dispatchNow($job);
    }

}
