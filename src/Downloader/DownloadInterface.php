<?php
/**
 * Created by IntelliJ IDEA.
 * User: radic
 * Date: 6/11/16
 * Time: 5:12 PM
 */

namespace Codex\Addon\Git\Downloader;


interface DownloadInterface
{
    /**
     * Download the ref
     * @param $owner
     * @param $repo
     * @param $type
     * @param $ref
     *
     * @return DownloadInterface
     */
    public function download($owner, $repo, $ref);

    /**
     * Move the downloaded files to a directory
     * 
     * @param string $dirPath The path to move the files to
     *
     * @return boolean
     */
    public function moveTo($dirPath);
}
