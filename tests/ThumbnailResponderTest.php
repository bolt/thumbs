<?php
namespace Bolt\Thumbs\Tests;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

use Bolt\Application;
use Bolt\Configuration\ResourceManager;

use Bolt\Thumbs\ThumbnailResponder;
/**
 * Class to test correct operation and locations of resource manager class and extensions.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 **/


class ThumbnailResponderTest extends \PHPUnit_Framework_TestCase
{
    
    public $jpg;
    public $gif;
    public $png;
    
    public function setup()
    {
        $this->jpg = __DIR__."/images/generic-logo.jpg";
        $this->gif = __DIR__."/images/generic-logo.gif";
        $this->png = __DIR__."/images/generic-logo.png";
        require_once __DIR__."/../vendor/bolt/bolt/app/classes/lib.php";
    }
    
    
    public function testBasicRequestParsing()
    {
        $request = Request::create(
            "/thumbs/320x240c/generic-logo.jpg",
            "GET"
        );
        
        $responder = $this->initializeResponder($request);
        
        $parse = $responder->parseRequest();
        $this->assertEquals("320", $responder->width);
        $this->assertEquals("240", $responder->height);
        $this->assertEquals("crop", $responder->action);
        $this->assertEquals('generic-logo.jpg', $responder->file);
        
    }
    
    public function testParseWithSubdirectory()
    {
        $request = Request::create(
            "/thumbs/320x240c/subdir/generic-logo.jpg",
            "GET"
        );
        
        $responder = $this->initializeResponder($request);
        $this->assertEquals('subdir/generic-logo.jpg', $responder->file);

    }
    
    public function testResponse()
    {
        $request = Request::create(
            "/thumbs/320x240r/generic-logo.jpg",
            "GET"
        );
        
        $responder = $this->initializeResponder($request);
        $response = $responder->respond();
        $this->assertInstanceOf(Response::class, $response);
    }
    
    public function testBadResponse()
    {
        $request = Request::create(
            "/thumbs/320x240r/noexistent/generic-logo.jpg",
            "GET"
        );
        
        $this->setExpectedException(FileNotFoundException::class);
        
        $responder = $this->initializeResponder($request);
        $response = $responder->respond();
        $this->assertInstanceOf(Response::class, $response);
    }
    
    protected function initializeResponder($request)
    {
        $config = new ResourceManager(__DIR__);
        $config->setPath('cache', 'tmp/cache');
        $config->setPath('files', 'images');
        $config->compat();
        
        $app = new Application(array('resources'=>$config));
        $responder = new ThumbnailResponder($app, $request);
        return $responder;
    }
    
    
}