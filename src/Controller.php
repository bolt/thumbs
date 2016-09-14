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
        $toAction = function($value) {
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
            ->bind('thumb');

        // Aliases
        $ctr->get('/{alias}/{file}', 'controller.thumbnails:alias')
            ->assert('alias', '[a-zA-Z0-9-_]+')
            ->assert('file', '.+')
            ->bind('alias');

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
        // Set to default 404 image if restricted to aliases
        if($this->isRestricted($app, $request)) {
            $transaction = $this->defaultTransaction($app, $request);
            $thumbnail = $app['thumbnails']->respond($transaction);
            return new Response($thumbnail);
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
        $config = $app["config"]->get("theme/image_aliases/".$alias, false);

        // Set to default 404 image if alias does not exist
        if(!$config) {
            $transaction = $this->defaultTransaction($app, $request);
            $thumbnail = $app['thumbnails']->respond($transaction);
            return new Response($thumbnail);
        }

        $width  = isset($config["size"][0])  ? $config["size"][0]  : 0;
        $height = isset($config["size"][1])  ? $config["size"][1]  : 0;
        $action = isset($config["cropping"]) ? $config["cropping"] : Action::CROP;

        return $this->serve($app, $request, $file, $action, $width, $height);
    }

    /**
     * Serve a request for a thumbnail
     * @param Application $app
     * @param Request $request
     * @param string $file
     * @param string $action
     * @param int $width
     * @param int $height
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

        $transaction = new Transaction( $file, $action, new Dimensions( $width, $height ), $requestPath );

        $thumbnail = $app['thumbnails']->respond($transaction);

        return new Response($thumbnail);
    }

    /**
     * Check if thumbnail request for specific resolution is allowed
     * @param Application $app
     * @param Request $request
     * @return boolean
     */
    protected function isRestricted(Application $app, Request $request)
    {
        return $app["config"]->get("general/thumbnails/restrict_alias", false);
    }

    /**
     * Get the default error image on restriction errors or undefined aliases
     * @param Application $app
     * @param Request $request
     * @return Transaction
     */
    protected function defaultTransaction(Application $app, Request $request)
    {
        $requestPath = urldecode($request->getPathInfo());

        return new Transaction(
            $app["config"]->get("general/thumbnails/notfound_image"),
            Action::CROP,
            new Dimensions(
                $app["config"]->get("general/thumbnails/default_thumbnail")[0],
                $app["config"]->get("general/thumbnails/default_thumbnail")[1]
            ),
            $requestPath
        );
    }
}
