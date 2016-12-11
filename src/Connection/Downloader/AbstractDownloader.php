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
 * Date: 6/11/16
 * Time: 5:16 PM
 */

namespace Codex\Addon\Git\Connection\Downloader;


use Codex\Addon\Git\Connection\Connection;
use Codex\Addon\Git\Syncer;
use Codex\Projects\Project;
use Laradic\Filesystem\Filesystem;

abstract class AbstractDownloader implements DownloadInterface
{
    /** @var \Codex\Addon\Git\Syncer */
    protected $syncer;

    /** @var \Laradic\Filesystem\Filesystem */
    protected $fs;

    /** @var \Laradic\Git\Remotes\Remote */
    protected $remote;

    /** @var mixed */
    protected $docPath;

    /** @var mixed */
    protected $menuPath;

    /** @var mixed */
    protected $indexPath;

    /** @var \Codex\Projects\Project */
    protected $project;

    /**
     * AbstractDownloader constructor.
     * @param $connection
     *
     */
    public function __construct(Syncer $syncer, Filesystem $fs)
    {
        //public function __construct(Connection $connection, Filesystem $fs)
        $this->syncer = $syncer;
        $this->fs     = $fs;

        $this->project   = $syncer->getProject();
        $this->docPath   = $syncer->setting('sync.paths.docs');
        $this->menuPath  = $syncer->setting('sync.paths.menu');
        $this->indexPath = $syncer->setting('sync.paths.index');
        $this->remote    = $syncer->client();
        #$this->remote    = $connection->client($connection->setting('connection'));
    }

    /**
     * @return \Codex\Addon\Git\Syncer
     */
    public function getSyncer()
    {
        return $this->syncer;
    }

    /**
     * @param \Codex\Addon\Git\Syncer $syncer
     */
    public function setSyncer($syncer)
    {
        $this->syncer = $syncer;
    }

    /**
     * @return \Laradic\Filesystem\Filesystem
     */
    public function getFs()
    {
        return $this->fs;
    }

    /**
     * @param \Laradic\Filesystem\Filesystem $fs
     */
    public function setFs($fs)
    {
        $this->fs = $fs;
    }

}
