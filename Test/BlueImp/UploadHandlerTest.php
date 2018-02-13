<?php

namespace PunkAve\FileUploaderBundle\BlueImp\Tests;

use PunkAve\FileUploaderBundle\BlueImp\UploadHandler;

/**
 * Created by PhpStorm.
 * User: cebeling
 * Date: 30/07/15
 * Time: 10:30
 */
class UploadHandlerTest extends \PHPUnit_Framework_TestCase
{

    /**
     *
     */
    public function testUploadHandlerWithUmlauts()
    {
        $uploadHandler = new UploadHandlerMock();

        $uploadHandlerReflection = new \ReflectionClass(UploadHandler::class);
        $method = $uploadHandlerReflection->getMethod('trim_file_name');
        $method->setAccessible(true);
        $result = $method->invokeArgs($uploadHandler, ['../Ääüö.jpg', 'jpg', null]);
        $this->assertEquals('Ääüö.jpg', $result);
    }

}

/**
 * Class UploadHandlerMock
 * @package PunkAve\FileUploaderBundle\BlueImp\Tests
 */
class UploadHandlerMock extends UploadHandler
{
    /**
     *
     */
    public function __construct(){

    }
}

