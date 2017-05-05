<?php

namespace Bolt\Thumbs;

use Contao\ImagineSvg\Imagine as SvgImagine;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * Register thumbnails service.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class ServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Container $app)
    {
        $app['controller.thumbnails.mount_prefix'] = '/thumbs';
        $app['controller.thumbnails'] = function () {
            return new Controller();
        };
        $app['thumbnails'] = function ($app) {
            return new Responder(
                $app['thumbnails.creator'],
                $app['thumbnails.finder'],
                $app['thumbnails.error_image'],
                $app['thumbnails.filesystem_cache'],
                $app['thumbnails.cache'],
                $app['thumbnails.cache_time']
            );
        };

        $app['thumbnails.creator'] = function ($app) {
            return new Creator($app['thumbnails.limit_upscaling'], $app['imagine.svg']);
        };

        $app['imagine.svg'] = function () {
            return new SvgImagine();
        };

        $app['thumbnails.finder'] = function ($app) {
            return new Finder(
                $app['filesystem'],
                $app['thumbnails.filesystems'],
                $app['thumbnails.default_image']
            );
        };

        $app['thumbnails.filesystems'] = [];
        $app['thumbnails.filesystem_cache'] = null;
        $app['thumbnails.cache'] = null;

        $app['thumbnails.default_image'] = null;
        $app['thumbnails.default_imagesize'] = [];
        $app['thumbnails.error_image'] = null;
        $app['thumbnails.cache_time'] = null;
        $app['thumbnails.limit_upscaling'] = true;
        $app['thumbnails.only_aliases'] = false;
        $app['thumbnails.aliases'] = [];
    }
}
