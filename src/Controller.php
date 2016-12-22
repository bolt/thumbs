<?php

namespace Bolt\Thumbs;

use Bolt\Filesystem\Handler\Image\Dimensions;
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
class Controller implements ControllerProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function connect(Application $app)
    {
        /** @var ControllerCollection $ctr */
        $ctr = $app['controllers_factory'];

        // Specific resolutions
        $toInt = function ($value) {
            return intval($value);
        };
        $toAction = function ($value) {
            $actions = [
                'c' => Action::CROP,
                'r' => Action::RESIZE,
                'b' => Action::BORDER,
                'f' => Action::FIT,
            ];

            return isset($actions[$value]) ? $actions[$value] : Action::CROP;
        };
        $ctr->get('/{width}x{height}{action}/{file}', 'controller.thumbnails:thumbnail')
            ->assert('width', '\d+')
            ->convert('width', $toInt)
            ->assert('height', '\d+')
            ->convert('height', $toInt)
            ->assert('action', '[a-z]?')
            ->convert('action', $toAction)
            ->assert('file', '.+')
            ->bind('thumb')
        ;

        // Aliases
        $ctr->get('/{alias}/{file}', 'controller.thumbnails:alias')
            ->assert('alias', '[\w-]+')
            ->assert('file', '.+')
            ->bind('thumb_alias')
        ;

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
        // Return 403 response if restricted to aliases
        if ($this->isRestricted($app, $request)) {
            $app->abort(Response::HTTP_FORBIDDEN);
        }

        return $this->serve($app, $request, $file, $action, $width, $height);
    }

    /**
     * Returns a thumbnail response.
     *
     * @param Application $app
     * @param Request     $request
     * @param string      $file
     * @param string      $alias
     *
     * @return Response
     */
    public function alias(Application $app, Request $request, $file, $alias)
    {
        $config = isset($app['thumbnails.aliases'][$alias]) ? $app['thumbnails.aliases'][$alias] : false;

        // Set to default 404 image if alias does not exist
        if (!$config) {
            return $this->defaultResponse($app, $request);
        }

        $width = isset($config['size'][0]) ? $config['size'][0] : 0;
        $height = isset($config['size'][1]) ? $config['size'][1] : 0;
        $action = isset($config['cropping']) ? $config['cropping'] : Action::CROP;

        return $this->serve($app, $request, $file, $action, $width, $height);
    }

    /**
     * Serve a request for a thumbnail
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
    protected function serve(Application $app, Request $request, $file, $action, $width, $height)
    {
        if (strpos($file, '@2x') !== false) {
            $file = str_replace('@2x', '', $file);
            $width *= 2;
            $height *= 2;
        }

        $requestPath = urldecode($request->getPathInfo());

        $transaction = new Transaction($file, $action, new Dimensions($width, $height), $requestPath);

        $thumbnail = $app['thumbnails']->respond($transaction);

        return new Response($thumbnail);
    }

    /**
     * Check if thumbnail request for specific resolution is allowed
     *
     * @param Application $app
     * @param Request     $request
     *
     * @return boolean
     */
    protected function isRestricted(Application $app, Request $request)
    {
        $session = $request->getSession();
        $auth = $session && $session->isStarted() ? $session->get('authentication') : null;

        if ($auth && $auth->getUser()->getEnabled()) {
            return false;
        }

        return isset($app['thumbnails.only_aliases']) ? $app['thumbnails.only_aliases'] : false;
    }

    /**
     * Get the default error image on restriction errors or undefined aliases
     *
     * @param Application $app
     * @param Request     $request
     *
     * @return Response
     */
    protected function defaultResponse(Application $app, Request $request)
    {
        $requestPath = urldecode($request->getPathInfo());

        $size = $app['thumbnails.default_imagesize'];

        $transaction = new Transaction(
            $app['thumbnails.default_image'],
            Action::CROP,
            new Dimensions($size[0], $size[1]),
            $requestPath
        );

        $thumbnail = $app['thumbnails']->respond($transaction);

        return new Response($thumbnail);
    }
}
