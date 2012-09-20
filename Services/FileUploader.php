<?php

namespace PunkAve\FileUploaderBundle\Services;

class FileUploader
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

        system("rm -rf " . escapeshellarg($folder));
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
            system("rsync -a --delete " . escapeshellarg($from . '/') . " " . escapeshellarg($to), $result);
            if ($result !== 0)
            {
                throw new \Exception("Sync failed");
            }
            if (isset($options['remove_from_folder']) && $options['remove_from_folder'])
            {
                system("rm -rf " . escapeshellarg($from));
            }
        }
        else
        {
            // A missing from_folder is not an error. This is commonly the case
            // when syncing from something that has nothing attached to it yet, etc.
        }
    }

    /**
     * Handles a file upload. Call this from an action, after validating the user's
     * right to upload and delete files and determining your 'folder' option. A good
     * example:
     *
     * $id = $this->getRequest()->get('id');
     * // Validate the id, make sure it's just an integer, validate the user's right to edit that 
     * // object, then...
     * $this->get('punkave.file_upload').handleFileUpload(array('folder' => 'photos/' . $id))
     * 
     * DOES NOT RETURN. The response is generated in native PHP by BlueImp's UploadHandler class.
     *
     * Note that if %file_uploader.file_path%/$folder already contains files, the user is 
     * permitted to delete those in addition to uploading more. This is why we use a
     * separate folder for each object's associated files.
     *
     * Any passed options are merged with the service parameters. You must specify
     * the 'folder' option to distinguish this set of uploaded files
     * from others.
     *
     */
    public function handleFileUpload($options = array())
    {
        if (!isset($options['folder']))
        {
            throw new \Exception("You must pass the 'folder' option to distinguish this set of files from others");
        }

        $options = array_merge($this->options, $options);

        $allowedExtensions = $options['allowed_extensions'];

        // Build a regular expression like /(\.gif|\.jpg|\.jpeg|\.png)$/i
        $allowedExtensionsRegex = '/(' . implode('|', array_map(function($extension) { return '\.' . $extension; }, $allowedExtensions)) . ')$/i';

        $sizes = (isset($options['sizes']) && is_array($options['sizes'])) ? $options['sizes'] : array();

        $filePath = $options['file_base_path'] . '/' . $options['folder'];
        $webPath = $options['web_base_path'] . '/' . $options['folder'];

        foreach ($sizes as &$size)
        {
            $size['upload_dir'] = $filePath . '/' . $size['folder'] . '/';
            $size['upload_url'] = $webPath . '/' . $size['folder'] . '/';
        }

        $originals = $options['originals'];

        $uploadDir = $filePath . '/' . $originals['folder'] . '/';

        foreach ($sizes as &$size)
        {
            @mkdir($size['upload_dir'], 0777, true);
        }

        @mkdir($uploadDir, 0777, true);

        $upload_handler = new \PunkAve\FileUploaderBundle\BlueImp\UploadHandler(
            array(
                'upload_dir' => $uploadDir, 
                'upload_url' => $webPath . '/' . $originals['folder'] . '/', 
                'script_url' => $options['request']->getUri(),
                'image_versions' => $sizes,
                'accept_file_types' => $allowedExtensionsRegex
            ));

        // From https://github.com/blueimp/jQuery-File-Upload/blob/master/server/php/index.php
        // There's lots of REST fanciness here to support different upload methods, so we're
        // keeping the blueimp implementation which goes straight to the PHP standard library.
        // TODO: would be nice to port that code fully to Symfonyspeak.

        header('Pragma: no-cache');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Content-Disposition: inline; filename="files.json"');
        header('X-Content-Type-Options: nosniff');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: OPTIONS, HEAD, GET, POST, PUT, DELETE');
        header('Access-Control-Allow-Headers: X-File-Name, X-File-Type, X-File-Size');

        switch ($_SERVER['REQUEST_METHOD']) {
            case 'OPTIONS':
                break;
            case 'HEAD':
            case 'GET':
                $upload_handler->get();
                break;
            case 'POST':
                if (isset($_REQUEST['_method']) && $_REQUEST['_method'] === 'DELETE') {
                    $upload_handler->delete();
                } else {
                    $upload_handler->post();
                }
                break;
            case 'DELETE':
                $upload_handler->delete();
                break;
            default:
                header('HTTP/1.1 405 Method Not Allowed');
        }

        // Without this Symfony will try to respond; the BlueImp upload handler class already did,
        // so it's time to hush up
        exit(0);
    }
}
