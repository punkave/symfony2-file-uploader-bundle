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
    public function testGetExtensionsFromRegex()
    {
        $regex = '/(\.jpg|\.jpeg|\.gif|\.png|\.pdf)$/i';
        $uploadHandler = new UploadHandlerMock();
        $extensions = $uploadHandler->get_accepted_extensions_from_options_regex_wrapper($regex);

        $this->assertCount(5, $extensions);
        $this->assertEquals('.jpg', $extensions[0]);
        $this->assertEquals('.jpeg', $extensions[1]);
        $this->assertEquals('.gif', $extensions[2]);
        $this->assertEquals('.png', $extensions[3]);
        $this->assertEquals('.pdf', $extensions[4]);

        $regex = '/()$/i';
        $extensions = $uploadHandler->get_accepted_extensions_from_options_regex_wrapper($regex);
        $this->assertCount(0, $extensions);

    }

    /**
     *
     */
    public function testIsBasenameExisting()
    {
        $regex = '/(\.jpg|\.jpeg|\.gif|\.png|\.pdf)$/i';
        $dir = __DIR__ . '/../upload-dir/';
        $uploadHandler = new UploadHandlerMock();

        $result = $uploadHandler->is_basename_existing_wrapper($dir, 'test.png', $regex);
        $this->assertTrue($result);

        $result = $uploadHandler->is_basename_existing_wrapper($dir, 'I-do-not-exist.jpg', $regex);
        $this->assertFalse($result);

        $regex = '/(\.pdf)$/i';
        $result = $uploadHandler->is_basename_existing_wrapper($dir, 'test.jpg', $regex);
        $this->assertFalse($result);

        $regex = '/()$/i';
        $result = $uploadHandler->is_basename_existing_wrapper($dir, 'test.jpg', $regex);
        $this->assertFalse($result);
    }

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

    public function get_accepted_extensions_from_options_regex_wrapper($regex)
    {
        return $this->get_accepted_extensions_from_options_regex($regex);
    }

    public function is_basename_existing_wrapper($directory, $filename, $regex)
    {
        return $this->is_basename_existing($directory, $filename, $regex);
    }
}

