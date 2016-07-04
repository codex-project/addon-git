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
 * Time: 5:12 PM
 */

namespace Codex\Addon\Git\Connection\Downloader;


use Sebwite\Support\Str;
use Symfony\Component\Process\ExecutableFinder;
use ZipArchive;

class ZipDownloader extends AbstractDownloader
{
    protected static $hasSystemUnzip;

    protected $path;

    protected $owner, $repo, $ref, $tmpPath;

    public function download($owner, $repo, $ref)
    {
        // Here we download and extract the zip file into a temporary location
        // The extracted files will then be copied to the documentation's ref dir
        // after which the extracted stuff will be cleaned up.

        // Check up on method to zip
        $this->hasUnzipCapabilities();

        $this->owner = $owner;
        $this->repo = $repo;
        $this->ref = $ref;

        $fs  = $this->getFs();
        $pfs = $this->project->getFiles();


        // prepare new temporary directory
        $tmpPath = storage_path("codex/git/{$owner}.{$repo}.{$ref}");
        if ( $fs->isDirectory($tmpPath) )
        {
            $fs->deleteDirectory($tmpPath);
        }
        $fs->ensureDirectory($tmpPath);

        // todo implement https://developer.github.com/v3/repos/contents/#get-archive-link
        $zip = new ZipArchive;
        $zip->open($this->remote->downloadArchive($repo, $ref, "{$tmpPath}.zip", $owner));
        $zip->extractTo($tmpPath);

        $tmpExtractedPath = $fs->globule($glob = path_join($tmpPath, '*'))[ 0 ];

        if ( $fs->exists($tmpIndexPath = path_join($tmpExtractedPath, $this->indexPath)) )
        {
            $pfs->put(path_join($ref, 'index.md'), $fs->get($tmpIndexPath));
        }
        if ( $fs->exists($tmpMenuPath = path_join($tmpExtractedPath, $this->menuPath)) )
        {
            $pfs->put(path_join($ref, 'menu.yml'), $fs->get($tmpMenuPath));
        }

        $files            = collect($fs->allFiles(path_join($tmpExtractedPath, $this->docPath)))
            ->transform(function ($item)
            {
                return (string)$item;
            })
            ->toArray();

        $total = count($files);
        foreach ( $files as $current => $filePath )
        {
            $destPath = Str::ensureRight(path_join($tmpExtractedPath, $this->docPath), DIRECTORY_SEPARATOR);
            $destPath = Str::removeLeft($filePath, $destPath);
            $pfs->put(path_join($ref, $destPath), $fs->get($filePath));
            $this->getSyncer()->fire('tick.file', [ $current, $total, $destPath, $this->syncer, $this ]);
        }

        $pfs->put(path_join($ref, 'index.md'), $fs->get($tmpIndexPath));
        $pfs->put(path_join($ref, 'menu.yml'), $fs->get($tmpMenuPath));
        $fs->deleteDirectory($tmpPath);
        $fs->delete("{$tmpPath}.zip");
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function moveTo($dirPath)
    {
    }

    protected function hasUnzipCapabilities()
    {
        if ( null === self::$hasSystemUnzip )
        {
            $finder               = new ExecutableFinder;
            self::$hasSystemUnzip = (bool)$finder->find('unzip');
        }
        if ( ! class_exists('ZipArchive') && ! self::$hasSystemUnzip )
        {
            // php.ini path is added to the error message to help users find the correct file
            $iniPath = php_ini_loaded_file();
            if ( $iniPath )
            {
                $iniMessage = 'The php.ini used by your command-line PHP is: ' . $iniPath;
            }
            else
            {
                $iniMessage = 'A php.ini file does not exist. You will have to create one.';
            }
            $error = "The zip extension and unzip command are both missing, skipping.\n" . $iniMessage;
            throw new \RuntimeException($error);
        }
    }
}
