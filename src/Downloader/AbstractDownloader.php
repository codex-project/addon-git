<?php
/**
 * Created by IntelliJ IDEA.
 * User: radic
 * Date: 6/11/16
 * Time: 5:16 PM
 */

namespace Codex\Addon\Git\Downloader;


use Codex\Addon\Git\Syncer;
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
     * @param $syncer
     */
    public function __construct(Syncer $syncer, Filesystem $fs)
    {
        $this->syncer = $syncer;
        $this->fs     = $fs;

        $this->project   = $syncer->getProject();
        $this->docPath   = $syncer->setting('sync.paths.docs');
        $this->menuPath  = $syncer->setting('sync.paths.menu');
        $this->indexPath = $syncer->setting('sync.paths.index');
        $this->remote    = $syncer->client($syncer->setting('connection'));
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
