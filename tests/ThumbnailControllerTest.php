<?php

namespace Bolt\Thumbs\Tests;

use Bolt\Filesystem\Image;
use Bolt\Thumbs\Dimensions;
use Bolt\Thumbs\Thumbnail;
use Bolt\Thumbs\ThumbnailController;
use Silex\Application;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\WebTestCase;

class ThumbnailControllerTest extends WebTestCase
{
    /** @var Application */
    protected $app;

    public function getRoutesToTest()
    {
        return [
            'crop action'    => ['/thumbs/123x456c/herp/derp.png', 'herp/derp.png', 'crop', 123, 456],
            'resize action'  => ['/thumbs/123x456r/herp/derp.png', 'herp/derp.png', 'resize', 123, 456],
            'border action'  => ['/thumbs/123x456b/herp/derp.png', 'herp/derp.png', 'border', 123, 456],
            'fit action'     => ['/thumbs/123x456f/herp/derp.png', 'herp/derp.png', 'fit', 123, 456],
            'default action' => ['/thumbs/123x456/herp/derp.png', 'herp/derp.png', 'crop', 123, 456],
            'unknown action' => ['/thumbs/123x456z/herp/derp.png', 'herp/derp.png', 'crop', 123, 456],
            'double size'    => ['/thumbs/123x456c/herp/derp@2x.png', 'herp/derp.png', 'crop', 246, 912],
        ];
    }

    /**
     * @dataProvider getRoutesToTest
     */
    public function testRoutes($path, $file, $action, $width, $height)
    {
        $client = $this->createClient();

        $this->mockResponder($path, $file, $action, $width, $height);
        $client->request('GET', $path);
    }

    /**
     * {@inheritdoc}
     */
    public function createApplication()
    {
        $app = new Application();
        $app['controller.thumbnails'] = new ThumbnailController();
        $app->mount('/thumbs', $app['controller.thumbnails']);
        $app->register(new ServiceControllerServiceProvider());

        $mock = $this->getMock('Bolt\Thumbs\ThumbnailResponder', ['respond'], [], '', false);
        $app['thumbnails'] = $mock;

        return $app;
    }

    protected function mockResponder($path, $file, $action, $width, $height)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject $mock */
        $mock = $this->app['thumbnails'];
        $mock->expects($this->once())
            ->method('respond')
            ->with($path, $file, $action, new Dimensions($width, $height))
            ->willReturn(new Thumbnail(new Image(), null))
        ;
    }
}
