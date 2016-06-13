<?php
/**
 * Created by IntelliJ IDEA.
 * User: radic
 * Date: 6/11/16
 * Time: 5:12 PM
 */

namespace Codex\Addon\Git\Downloader;


use Sebwite\Support\Arr;

class GitDownloader extends AbstractDownloader
{

    /**
     * Download the ref
     *
     * @param $owner
     * @param $repo
     * @param $type
     * @param $ref
     *
     * @return DownloadInterface
     */
    public function download($owner, $repo, $ref)
    {
        // local filesystem
        $fs = $this->getFs();
        // projects filesystem
        $pfs = $this->project->getFiles();
        // remote filesystem (repo on github/bitbucket)
        $rfs = $this->remote->getFilesystem($repo, $owner, $ref);

        $files = $rfs->allFiles($this->docPath);
        $files = Arr::without($files, [ $this->indexPath, $this->menuPath ]);
        $total = count($files);

        if ( !$rfs->exists($this->indexPath) )
        {
            return $this->syncer->log('error', 'syncRef could not find the index file', [ $this->indexPath ]);
        }

        if ( !$rfs->exists($this->menuPath) )
        {
            return $this->syncer->log('error', 'syncRef could not find the menu file', [ $this->menuPath ]);
        }

        foreach ( $files as $current => $filePath )
        {
            $destPath = path_relative($filePath, $this->docPath);
            if ( $rfs->exists($filePath) )
            {
                $pfs->put(path_join($ref, $destPath), $rfs->get($filePath));
            }
            $this->getSyncer()->fire('tick.file', [ $current, $total, $filePath, $this->syncer, $this ]);
        }

        // index and menu resolving
        $pfs->put(path_join($ref, 'index.md'), $rfs->get($this->indexPath));
        $pfs->put(path_join($ref, 'menu.yml'), $rfs->get($this->menuPath));

        return $this;
    }

    /**
     * Move the downloaded files to a directory
     *
     * @param string $dirPath The path to move the files to
     *
     * @return boolean
     */
    public function moveTo($dirPath)
    {
        // TODO: Implement moveTo() method.
    }
}
