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
 * Date: 6/11/16
 * Time: 5:16 PM
 */

namespace Codex\Addon\Git\Connection\Downloader;


use Codex\Addon\Git\Connection\Connection;
use Sebwite\Filesystem\Filesystem;

abstract class AbstractDownloader implements DownloadInterface
{
    /** @var \Codex\Addon\Git\Syncer */
    protected $syncer;

    /** @var \Sebwite\Filesystem\Filesystem */
    protected $fs;

    /** @var \Sebwite\Git\Remotes\Remote */
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
     *
     * @param $connection
     */
    public function __construct(Connection $connection, Filesystem $fs)
    {
        $this->syncer = $connection;
        $this->fs     = $fs;

        $this->project   = $connection->getGitProject()->getProject();
        $this->docPath   = $connection->getDocsPath();
        $this->menuPath  = $connection->getMenuPath();
        $this->indexPath = $connection->getIndexPath();
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
     * @return \Sebwite\Filesystem\Filesystem
     */
    public function getFs()
    {
        return $this->fs;
    }

    /**
     * @param \Sebwite\Filesystem\Filesystem $fs
     */
    public function setFs($fs)
    {
        $this->fs = $fs;
    }

}
