<?php

namespace PunkAve\FileUploaderBundle\Twig;

use Twig_Extension;
use Twig_Function_Method;
use Symfony\Component\DependencyInjection\Container;

class FileExtension extends Twig_Extension
{
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function getFunctions()
    {
        return array(
            'punkave_get_files' => new Twig_Function_Method($this, 'getFiles'),
        );
    }

    public function getFiles($folder)
    {
        return $this->container->get('punk_ave.file_uploader')->getFiles(array('folder' => $folder));
    }

    public function getName()
    {
        return 'punkave_file';
    }
}
