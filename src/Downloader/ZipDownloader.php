<?php
/**
 * Created by IntelliJ IDEA.
 * User: radic
 * Date: 6/11/16
 * Time: 5:12 PM
 */

namespace Codex\Addon\Git\Downloader;


use Sebwite\Support\Str;
use Symfony\Component\Process\ExecutableFinder;
use ZipArchive;

class ZipDownloader extends AbstractDownloader
{
    protected static $hasSystemUnzip;

    protected $path;

    public function download($owner, $repo, $type, $ref)
    {

        if ( null === self::$hasSystemUnzip )
        {
            $finder               = new ExecutableFinder;
            self::$hasSystemUnzip = (bool)$finder->find('unzip');
        }
        if ( !class_exists('ZipArchive') && !self::$hasSystemUnzip )
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

        $fs      = $this->getFs();
        $pfs     = $this->project->getFiles();
        $tmpPath = storage_path("codex/git/{$owner}.{$repo}.{$ref}");
        $fs->ensureDirectory($tmpPath);

        $zip = new ZipArchive;
        $zip->open($this->remote->downloadArchive($repo, $ref, "{$tmpPath}.zip", $owner));
        $zip->extractTo($tmpPath);
        $tmpExtractedPath = path_join($tmpPath, "{$repo}-{$ref}");

        $files = collect($fs->allFiles(path_join($tmpExtractedPath, $this->docPath)))
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
            $this->getSyncer()->fire('tick.file', [ $current, $total, $filePath, $this->syncer, $this ]);
        }

        $pfs->put(path_join($ref, 'index.md'), $fs->get(path_join($tmpExtractedPath, $this->indexPath)));
        $pfs->put(path_join($ref, 'menu.yml'), $fs->get(path_join($tmpExtractedPath, $this->menuPath)));

        $fs->deleteDirectory($tmpExtractedPath);
        $fs->delete()
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function moveTo($dirPath)
    {
    }
}
