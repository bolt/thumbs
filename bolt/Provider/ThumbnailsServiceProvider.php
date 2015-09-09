<?php

namespace Bolt\Provider;

use Bolt\Events\ControllerEvents;
use Bolt\Events\MountEvent;
use Bolt\Thumbs\ImageResource;
use Bolt\Thumbs\ThumbnailController;
use Bolt\Thumbs\ThumbnailCreator;
use Bolt\Thumbs\ThumbnailResponder;
use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Register thumbnails service.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class ThumbnailsServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        $app['controller.thumbnails.mount_prefix'] = '/thumbs';
        $app['controller.thumbnails'] = $app->share(function () {
            return new ThumbnailController();
        });

        $app['thumbnails'] = $app->share(function ($app) {
            return new ThumbnailResponder(
                $app['thumbnails.creator'],
                $app['filesystem'],
                $app['thumbnails.filesystems'],
                $app['thumbnails.default_image'],
                $app['thumbnails.error_image'],
                $app['thumbnails.save_files'] ? $app['thumbnails.filesystem_cache'] : null,
                $app['thumbnails.cache'],
                $app['thumbnails.cache_time'],
                $app['thumbnails.allow_upscale']
            );
        });

        $app['thumbnails.creator'] = $app->share(function () {
            return new ThumbnailCreator();
        });

        $app['thumbnails.filesystems'] = [
            'files',
            'themebase',
        ];
        $app['thumbnails.filesystem_cache'] = $app->share(function ($app) {
            return $app['filesystem']->getFilesystem('web');
        });
        $app['thumbnails.cache'] = $app->share(function ($app) {
            return $app['cache'];
        });

        $app['thumbnails.default_image'] = $app->share(function ($app) {
            $image = $app['config']->get('general/thumbnails/notfound_image');
            if ($image === null) {
                return null;
            }
            return $app['filesystem']->getImage($image);
        });

        $app['thumbnails.error_image'] = $app->share(function ($app) {
            $image = $app['config']->get('general/thumbnails/error_image');
            if ($image === null) {
                return null;
            }
            return $app['filesystem']->getImage($image);
        });

        $app['thumbnails.cache_time'] = $app['config']->get('general/thumbnails/browser_cache_time');

        $app['thumbnails.allow_upscale'] = $app['config']->get('general/thumbnails/allow_upscale');

        $app['thumbnails.save_files'] = $app['config']->get('general/thumbnails/save_files');

        ImageResource::setNormalizeJpegOrientation($app['config']->get('general/thumbnails/exif_orientation', true));
        ImageResource::setQuality($app['config']->get('general/thumbnails/quality', 80));
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
        $app['dispatcher']->addListener(ControllerEvents::MOUNT, function (MountEvent $event) {
            $app = $event->getApp();
            $event->mount($app['controller.thumbnails.mount_prefix'], $app['controller.thumbnails']);
        });
    }
}
