<?php

namespace Bolt\Thumbs;

use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for the thumbnail route.
 * Passes the parsed request to the service.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class ThumbnailController implements ControllerProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function connect(Application $app)
    {
        /** @var ControllerCollection $ctr */
        $ctr = $app['controllers_factory'];

        $toInt = function ($value) {
            return intval($value);
        };
        $toAction = function($value) {
            $actions = [
                'c' => 'crop',
                'r' => 'resize',
                'b' => 'border',
                'f' => 'fit',
            ];
            return isset($actions[$value]) ? $actions[$value] : 'crop';
        };
        $ctr->get('/{width}x{height}{action}/{file}', 'controller.thumbnails:thumbnail')
            ->assert('width', '\d+')
            ->convert('width', $toInt)
            ->assert('height', '\d+')
            ->convert('width', $toInt)
            ->assert('action', '[a-z]?')
            ->convert('action', $toAction)
            ->assert('file', '.+')
            ->bind('thumb');

        return $ctr;
    }

    /**
     * Returns a thumbnail response.
     *
     * @param Application $app
     * @param Request     $request
     * @param string      $file
     * @param string      $action
     * @param int         $width
     * @param int         $height
     *
     * @return Response
     */
    public function thumbnail(Application $app, Request $request, $file, $action, $width, $height)
    {
        if (strpos($file, '@2x') !== false) {
            $file = str_replace('@2x', '', $file);
            $width *= 2;
            $height *= 2;
        }

        $requestPath = urldecode($request->getPathInfo());
        $thumbnail = $app['thumbnails']->respond($requestPath, $file, $action, new Dimensions($width, $height));

        return new Response($thumbnail);
    }
}
