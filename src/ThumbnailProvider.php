<?php
use Silex\Application;
use Silex\ControllerProviderInterface;

class ThumbnailProvider implements ControllerProviderInterface
{
    
    public $app;
    
    public function connect(Application $app)
    {
        // creates a new controller based on the default route
        $controllers = $app['controllers_factory'];

        $controllers->get('{thumb}', function (Application $app) {
            $request = $app['request'];
            $action = new ThumbnailResponder($app, $request);
            if($response = $action->respond()) {
                return $response;
            } else {
                $app->pass();
            }
        });

    }
}
