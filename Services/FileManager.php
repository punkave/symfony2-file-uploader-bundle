<?php

namespace PunkAve\FileUploaderBundle\Services;

use Symfony\Component\Filesystem\Filesystem;
use \Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class FileManager extends Filesystem
{
    protected $options;

    public function __construct($options)
    {
        if (!strlen(trim($options['file_base_path']))) {
            throw \Exception("file_base_path option looks empty, bailing out");
        }
        $this->options = $options;
    }
    /**
     * Get a list of files already present. The 'folder' option is required.
     * If you pass consistent options to this method and handleFileUpload with
     * regard to paths, then you will get consistent results.
     */
    public function getFiles($options = array())
    {
        $options = array_merge($this->options, $options);
        $ret = array();
        $folder = $options['file_base_path'] . DIRECTORY_SEPARATOR . $options['folder'];
        if ($this->exists($folder)) {
            $finder = new Finder();
            $finder->in($folder.DIRECTORY_SEPARATOR.'originals');
            $fullPath = isset($options['full_path']) ? $options['full_path'] : false;
            foreach($finder as $entry) {
                /** @var $entry SplFileInfo */
                array_push($ret, $fullPath?$entry->getRealPath():$entry->getBasename());
            }
        }
        return $ret;
    }

    /**
     * Remove the folder specified by 'folder' and its contents.
     * If you pass consistent options to this method and handleFileUpload with
     * regard to paths, then you will get consistent results.
     * @param array $options
     * @throws IOException
     */
    public function removeFiles($options = array())
    {
        $folder = $options['folder'];
        if (!strlen(trim($folder))) {
            throw \Exception("folder option looks empty, bailing out");
        }

        // Remove folder, let the caller deal with an IO exception in case of error
        $this->remove($this->options['file_base_path'] . DIRECTORY_SEPARATOR . $folder);
    }

    /**
     * Sync existing files from one folder to another. The 'fromFolder' and 'toFolder'
     * options are required. As with the 'folder' option elsewhere, these are appended
     * to the file_base_path for you, missing parent folders are created, etc. If
     * 'fromFolder' does not exist no error is reported as this is common if no files
     * have been uploaded. If there are files and the sync reports errors an exception
     * is thrown.
     *
     * If you pass consistent options to this method and handleFileUpload with
     * regard to paths, then you will get consistent results.
     */
    public function syncFiles($options = array())
    {
        $options = array_merge($this->options, $options);

        // We're syncing and potentially deleting folders, so make sure
        // we were passed something - make it a little harder to accidentally
        // trash your site
        if (!strlen(trim($options['from_folder']))) {
            throw \Exception("from_folder option looks empty, bailing out");
        }
        if (!strlen(trim($options['to_folder']))) {
            throw \Exception("to_folder option looks empty, bailing out");
        }

        $from = $options['file_base_path'] . DIRECTORY_SEPARATOR . $options['from_folder'];
        $to = $options['file_base_path'] . DIRECTORY_SEPARATOR . $options['to_folder'];
        if ($this->exists($from)) {
            $this->mirror($from, $to);
            if (isset($options['remove_from_folder']) && $options['remove_from_folder']) {
                $this->remove($from);
            }
        } else {
            // A missing from_folder is not an error. This is commonly the case
            // when syncing from something that has nothing attached to it yet, etc.
        }
    }
}
