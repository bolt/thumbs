<?php
namespace Bolt\Thumbs\Tests;

use Bolt\Application;
use Bolt\Configuration\ResourceManager;
use Bolt\Provider\CacheServiceProvider;
use Bolt\Thumbs\ThumbnailResponder;
use Eloquent\Pathogen\FileSystem\Factory\PlatformFileSystemPathFactory;
use FilesystemIterator;
use Pimple;
use Symfony\Component\Finder\Iterator\RecursiveDirectoryIterator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ThumbnailResponderTest extends \PHPUnit_Framework_TestCase
{

    public function setup()
    {
        @mkdir(__DIR__ . '/tmp/cache/', 0777, true);
    }

    public function testBasicRequestParsing()
    {
        $request = Request::create(
            '/thumbs/320x240c/generic-logo.jpg',
            'GET'
        );

        $responder = $this->initializeResponder($request);

        $parse = $responder->parseRequest();
        $this->assertEquals('320', $responder->width);
        $this->assertEquals('240', $responder->height);
        $this->assertEquals('crop', $responder->action);
        $this->assertEquals('generic-logo.jpg', $responder->file);
    }

    public function testParseWithSubdirectory()
    {
        $request = Request::create(
            '/thumbs/320x240c/subdir/generic-logo.jpg',
            'GET'
        );

        $responder = $this->initializeResponder($request);
        $this->assertEquals('subdir/generic-logo.jpg', $responder->file);
    }

    public function testResponse()
    {
        $request = Request::create(
            '/thumbs/320x240r/generic-logo.jpg',
            'GET'
        );

        $responder = $this->initializeResponder($request);
        $response = $responder->respond();
        $this->assertInstanceOf(Response::class, $response);
    }

    protected function initializeResponder($request)
    {
        $container = new Pimple(
            array(
                'rootpath' => __DIR__,
                'pathmanager' => new PlatformFileSystemPathFactory()
            )
        );

        $config = new ResourceManager($container);
        $config->setPath('cache', 'tmp/cache');
        $config->setPath('files', 'images');
        $config->compat();

        $app = new Application(array('resources' => $config));
        $app->register(new CacheServiceProvider());

        $responder = new ThumbnailResponder($app, $request);
        $responder->initialize();

        return $responder;
    }

    public function tearDown()
    {
        $this->rmdir(__DIR__ . '/tmp');
        @rmdir(__DIR__ . '/tmp');
    }

    protected function rmdir($dir)
    {
        $iterator = new \RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
    }
}
