<?php
namespace Bolt\Thumbs;

use Silex\Application;
use Silex\ControllerProviderInterface;

class ThumbnailProvider implements ControllerProviderInterface
{
    
    
    public function connect(Application $app)
    {
        // creates a new controller based on the default route
        $controllers = $app['controllers_factory'];

        $controllers->get('/thumbs/{thumb}', function (Application $app) {
            $request = $app['request'];
            $action = new ThumbnailResponder($app, $request);
            if($response = $action->respond()) {
                return $response;
            } else {
                $app->pass();
            }
        });
        return $controllers;
    }
}
