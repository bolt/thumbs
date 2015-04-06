<?php
namespace Bolt\Thumbs\Tests;

use Bolt\Application;
use Bolt\Configuration\Standard;
use Bolt\Thumbs\ThumbnailProvider;
use Symfony\Component\HttpFoundation\Request;

class ThumbnailProviderTest extends \PHPUnit_Framework_TestCase
{
    
    public function setup()
    {
        @mkdir(__DIR__ . '/app/database', 0777, true);
        @mkdir(__DIR__ . '/app/cache', 0777, true);
    }
    
    public function testRegister()
    {
        $app = new Application(array('resources'=>new Standard(__DIR__)));
        $provider = new ThumbnailProvider();
        $app->register($provider);
        $this->assertArrayHasKey('files', $app['thumbnails.paths']);
        $this->assertArrayHasKey('theme', $app['thumbnails.paths']);
        $app['request'] = Request::create('/');
        $this->assertInstanceOf('Bolt\Thumbs\ThumbnailResponder', $app['thumbnails']);
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $app['thumbnails.response']);
    }
    
    public function testConnect()
    {
        $app = new Application(array('resources'=>new Standard(__DIR__)));
        $provider = new ThumbnailProvider();
        $provider->connect($app);
        
    }
    
    public function testBoot()
    {
        $app = new Application(array('resources'=>new Standard(__DIR__)));
        $provider = new ThumbnailProvider();
        $provider->boot($app);
    }
    
    public function tearDown()
    {
        $this->rmdir(__DIR__ . '/app');
        @rmdir(__DIR__ . '/app');
    }
    
    protected function rmdir($dir)
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
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