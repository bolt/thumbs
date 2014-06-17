<?php
namespace Bolt\Thumbs;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class ThumbnailProvider implements ServiceProviderInterface, ControllerProviderInterface
{
    
    public function register(Application $app)
    {
        $app['thumbnails'] = $app->share(function ($app) { 
            $responder = new ThumbnailResponder($app, $app['request']);
            return $responder;
        });
    }
    
    
    public function connect(Application $app)
    {
        // creates a new controller based on the default route
        $controllers = $app['controllers_factory'];

        $controllers->get('/{thumb}', function (Application $app) {
            $action = $app['thumbnails'];
            if($response = $action->respond()) {
                return $response;
            } else {
                $app->pass();
            }
        })->assert('thumb', '.+');
        return $controllers;
    }
}
