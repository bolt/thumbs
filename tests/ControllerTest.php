<?php

namespace Bolt\Thumbs\Tests;

use Bolt\Filesystem\Adapter\Local;
use Bolt\Filesystem\Filesystem;
use Bolt\Filesystem\Handler\Image;
use Bolt\Filesystem\Handler\Image\Dimensions;
use Bolt\Thumbs\Controller;
use Bolt\Thumbs\Thumbnail;
use Bolt\Thumbs\Transaction;
use Silex\Application;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

class ControllerTest extends WebTestCase
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
     * Test alias restriction functionality
     */
    public function testIsRestricted()
    {
        $app = $this->createApplication();
        $app['thumbnails.only_aliases'] = false;
        $controller = new Controller();
        $request = Request::create('/thumbs/123x456c/herp/derp.png');
        $this->assertInstanceOf('Bolt\Thumbs\Response', $controller->thumbnail($app, $request, 'herp/derp.png', 'c', 123, 456));

        $app['thumbnails.only_aliases'] = true;
        $this->setExpectedException('Symfony\Component\HttpKernel\Exception\HttpException');
        $controller->thumbnail($app, $request, 'herp/derp.png', 'c', 123, 456);
    }

    public function testNotIsRestrictedWhenLoggedIn()
    {
        $app = $this->createApplication();
        $controller = new Controller();
        $request = Request::create('/thumbs/123x456c/herp/derp.png');

        $session = $this->getMock('Symfony\Component\HttpFoundation\Session\Session');
        $user = $this->getMock('stdClass', ['getEnabled']);
        $user->expects($this->any())
            ->method('getEnabled')
            ->willReturn(true);
        $auth = $this->getMock('stdClass', ['getUser']);
        $auth->expects($this->any())
            ->method('getUser')
            ->willReturn($user);
        $session->expects($this->any())
            ->method('get')
            ->with('authentication')
            ->willReturn($auth);
        $request->setSession($session);

        $app['thumbnails.only_aliases'] = true;
        $this->assertInstanceOf('Bolt\Thumbs\Response', $controller->thumbnail($app, $request, 'herp/derp.png', 'c', 123, 456));
    }

    /**
     * {@inheritdoc}
     */
    public function createApplication()
    {
        $app = new Application();
        $app['controller.thumbnails'] = new Controller();
        $app->mount('/thumbs', $app['controller.thumbnails']);
        $app->register(new ServiceControllerServiceProvider());

        $mock = $this->getMock('Bolt\Thumbs\ThumbnailResponder', ['respond'], [], '', false);
        $mock
            ->expects($this->any())
            ->method('respond')
            ->willReturn(new Thumbnail(new Image(new Filesystem(new Local(__DIR__))), ''))
        ;
        $app['thumbnails'] = $mock;

        return $app;
    }

    protected function mockResponder($path, $file, $action, $width, $height)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject $mock */
        $mock = $this->app['thumbnails'];
        $mock->expects($this->once())
            ->method('respond')
            ->with(new Transaction($file, $action, new Dimensions($width, $height), $path))
            ->willReturn(new Thumbnail(new Image(), null))
        ;
    }
}
