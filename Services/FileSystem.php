<?php
/**
 * Created by JetBrains PhpStorm.
 * User: A140980
 * Date: 14/11/12
 * Time: 17:45
 * To change this template use File | Settings | File Templates.
 */
namespace PunkAve\FileUploaderBundle\Services;

use Symfony\Component\Filesystem\Filesystem as BaseFileSystem;

class FileSystem extends BaseFileSystem
{
    /**
     * Mirrors a directory to another.
     *
     * @param string       $originDir The origin directory
     * @param string       $targetDir The target directory
     * @param \Traversable $iterator  A Traversable instance
     * @param array        $options   An array of boolean options
     *                               Valid options are:
     *                                 - $options['override'] Whether to override an existing file on copy or not (see copy())
     *                                 - $options['copy_on_windows'] Whether to copy files instead of links on Windows (see symlink())
     *                                 - $options['delete'] Default TRUE Whether to delete files that are not in the source directory
     *
     * @throws IOException When file type is unknown
     */
    public function mirror($originDir, $targetDir, \Traversable $iterator = null, $options = array())
    {
        $l_iterator = $iterator;
        if (strlen(trim($options['to_folder']))) {
            if (!isset($options['delete']) or $options['delete']) {
                if (null === $l_iterator) {
                    $flags = \FilesystemIterator::SKIP_DOTS;
                    $l_iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($targetDir, $flags), \RecursiveIteratorIterator::CHILD_FIRST );
                }

                $targetDir = rtrim($targetDir, '/\\');
                $originDir = rtrim($originDir, '/\\');

                foreach ($l_iterator as $file) {
                    $origin = str_replace($targetDir, $originDir, $file->getPathname());
                    if (!$this->exists($origin)) {
                        $this->remove($file);
                    }
                }
            }
        }
        parent::mirror($originDir, $targetDir, $iterator, $options);
    }
}
