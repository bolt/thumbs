<?php

namespace Bolt\Thumbs;

use Silex\Application;
use Silex\ServiceProviderInterface;

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
    public function register(Application $app)
    {
        $app['controller.thumbnails.mount_prefix'] = '/thumbs';
        $app['controller.thumbnails'] = $app->share(function () {
            return new Controller();
        });
        $app['thumbnails'] = $app->share(function ($app) {
            return new Responder(
                $app['thumbnails.creator'],
                $app['thumbnails.finder'],
                $app['thumbnails.error_image'],
                $app['thumbnails.filesystem_cache'],
                $app['thumbnails.cache'],
                $app['thumbnails.cache_time']
            );
        });

        $app['thumbnails.creator'] = $app->share(function ($app) {
            return new Creator($app['thumbnails.allow_upscale']);
        });

        $app['thumbnails.finder'] = $app->share(function ($app) {
            return new Finder(
                $app['filesystem'],
                $app['thumbnails.filesystems'],
                $app['thumbnails.default_image']
            );
        });

        $app['thumbnails.filesystems'] = [];
        $app['thumbnails.filesystem_cache'] = null;
        $app['thumbnails.cache'] = null;

        $app['thumbnails.default_image'] = null;
        $app['thumbnails.error_image'] = null;
        $app['thumbnails.cache_time'] = null;
        $app['thumbnails.allow_upscale'] = null;
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
    }
}
