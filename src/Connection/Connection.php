<?php
/**
 * Part of the Codex Project packages.
 *
 * License and copyright information bundled with this package in the LICENSE file.
 *
 * @author Robin Radic
 * @copyright Copyright 2016 (c) Codex Project
 * @license http://codex-project.ninja/license The MIT License
 */

/**
 * Created by IntelliJ IDEA.
 * User: radic
 * Date: 7/4/16
 * Time: 3:38 AM
 */

namespace Codex\Addon\Git\Connection;


use Codex\Addon\Git\Connection\Downloader\DownloadInterface;
use Codex\Addon\Git\Connection\Downloader\GitDownloader;
use Codex\Addon\Git\Connection\Downloader\ZipDownloader;
use Codex\Addon\Git\Exceptions\CodexGitException;
use Codex\Addon\Git\GitProject;
use Codex\Support\Collection;
use Illuminate\Contracts\Cache\Repository;
use Laradic\Git\Contracts\Manager;

class Connection
{
    /** @var GitProject */
    protected $gitProject;

    protected $name;

    protected $owner;

    protected $repository;

    protected $service;

    protected $downloader;

    protected $docsPath;

    protected $menuPath;

    protected $indexPath;

    protected $branches;

    protected $versions;

    protected $skipPatchVersions = false;

    protected $skipMinorVersions = false;

    protected $webhookEnabled;

    protected $webhookSecret;

    protected $config;

    /**
     * Connection constructor.
     *
     * @param                                        $name
     * @param \Codex\Addon\Git\GitProject            $gitProject
     * @param \Laradic\Git\Contracts\Manager         $git
     * @param \Illuminate\Contracts\Cache\Repository $cache
     */
    public function __construct($name, \Codex\Addon\Git\GitProject $gitProject, Manager $git, Repository $cache)
    {
        $this->name       = $name;
        $this->gitProject = $gitProject;
        $this->config     = new Collection;
    }

    public function hydrate(array $vars = [ ])
    {
        $this->config = new Collection($vars);
        $vars         = array_merge($vars, $vars[ 'sync' ][ 'constraints' ], $vars[ 'sync' ]);
        foreach ( $vars as $key => $val )
        {
            $key = camel_case($key);
            if ( property_exists($this, $key) )
            {
                $this->{$key} = $val;
            }
        }
        foreach ( $vars[ 'sync' ][ 'paths' ] as $key => $path )
        {
            $this->{$key . 'Path'} = $path;
        }
        $this->webhookEnabled = $vars[ 'webhook' ][ 'enabled' ];
        $this->webhookSecret  = $vars[ 'webhook' ][ 'secret' ];
    }

    public function client()
    {
        return app('laradic.git')->connection($this->service);
    }

    /**
     * @return mixed
     */
    public function getDocsPath()
    {
        return $this->docsPath;
    }

    /**
     * @return mixed
     */
    public function getMenuPath()
    {
        return $this->menuPath;
    }

    /**
     * @return mixed
     */
    public function getIndexPath()
    {
        return $this->indexPath;
    }

    /**
     * @return \Codex\Support\Collection
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return GitProject
     */
    public function getGitProject()
    {
        return $this->gitProject;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @return mixed
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * @return mixed
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * @return DownloadInterface
     */
    public function getDownloader()
    {
        if ( $this->downloader === 'git' )
        {
            $downloader = GitDownloader::class;
        }
        elseif ( $this->downloader === 'zip' )
        {
            $downloader = ZipDownloader::class;
        }
        else
        {
            throw CodexGitException::missingConfiguration('downlaoder not valid');
        }

        return app()->build($downloader, [ 'connection' => $this ]);
    }

    /**
     * @return mixed
     */
    public function getBranches()
    {
        return $this->branches;
    }

    /**
     * @return mixed
     */
    public function getVersions()
    {
        return $this->versions;
    }

    /**
     * @return boolean
     */
    public function isSkipPatchVersions()
    {
        return $this->skipPatchVersions;
    }

    /**
     * @return boolean
     */
    public function isSkipMinorVersions()
    {
        return $this->skipMinorVersions;
    }

    /**
     * @return mixed
     */
    public function getWebhookEnabled()
    {
        return $this->webhookEnabled;
    }

    /**
     * @return mixed
     */
    public function getWebhookSecret()
    {
        return $this->webhookSecret;
    }


}
