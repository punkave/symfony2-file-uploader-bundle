<?php

namespace PunkAve\FileUploaderBundle\Services;

class FileManager
{
    protected $options;

    public function __construct($options)
    {
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

        $folder = $options['file_base_path'] . '/' . $options['folder'];
        if (file_exists($folder))
        {
            $dirs = glob("$folder/originals/*");
            $fullPath = isset($options['full_path']) ? $options['full_path'] : false;
            if ($fullPath)
            {
                return $dirs;
            }
            $result = array_map(function($s) { return basename($s); }, $dirs);
            return $result;
        }
        else
        {
            return array();
        }
    }

    /**
     * Remove the folder specified by 'folder' and its contents.
     * If you pass consistent options to this method and handleFileUpload with
     * regard to paths, then you will get consistent results.
     */
    public function removeFiles($options = array())
    {
        $options = array_merge($this->options, $options);


        $folder = $options['file_base_path'] . '/' . $options['folder'];

        if (!strlen(trim($options['file_base_path'])))
        {
            throw \Exception("file_base_path option looks empty, bailing out");
        }

        if (!strlen(trim($options['folder'])))
        {
            throw \Exception("folder option looks empty, bailing out");
        }
    	if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
        {
	   //Running on Windows
	   system('rd /s/q ' . escapeshellarg($folder));
	}
        else
        {
	   //Not running on Windows. Hoping it's a Linux
    	   system("rm -rf " . escapeshellarg($folder));
	}
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
        if (!strlen(trim($options['file_base_path'])))
        {
            throw \Exception("file_base_path option looks empty, bailing out");
        }
        if (!strlen(trim($options['from_folder'])))
        {
            throw \Exception("from_folder option looks empty, bailing out");
        }
        if (!strlen(trim($options['to_folder'])))
        {
            throw \Exception("to_folder option looks empty, bailing out");
        }

        $from = $options['file_base_path'] . '/' . $options['from_folder'];
        $to = $options['file_base_path'] . '/' . $options['to_folder'];
        $slashes = substr_count($from, '/');
        if (file_exists($from))
        {
            if (isset($options['create_to_folder']) && $options['create_to_folder'])
            {
                @mkdir($to, 0777, true);
            }
            elseif (!file_exists($to))
            {
                throw new \Exception("to_folder does not exist");
            }
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
            {
                //Running on Windows
                system("robocopy /Mir /NP /NDL /NFL /NJH /NJS " . escapeshellarg($from . '/') . " " . escapeshellarg($to), $result);
                $kk=1;
            }
            else
            {
                 //Not running on Windows. Hoping it's a Linux
                 system("rsync -a --delete " . escapeshellarg($from . '/') . " " . escapeshellarg($to), $result);
                 $kk=0;
            }		
            if ($result !== $kk)
            {	
                throw new \Exception("Sync failed");
            }
            if (isset($options['remove_from_folder']) && $options['remove_from_folder'])
            {
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
                {
                    //Running on Windows
                    system('rd /s/q ' . escapeshellarg($from));
                }
                else
                {
                    //Not running on Windows. Hoping it's a Linux
                    system("rm -rf " . escapeshellarg($from));
                }
            }
        }
        else
        {
            // A missing from_folder is not an error. This is commonly the case
            // when syncing from something that has nothing attached to it yet, etc.
        }
    }
}
