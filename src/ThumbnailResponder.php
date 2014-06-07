<?php
namespace Bolt\Thumbs;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Bolt\Application;
use Gregwar\Cache\Cache;
use Symfony\Component\HttpFoundation\File\File;


class ThumbnailResponder
{
    public $app;
    public $request;
    public $actions = array(
        'c' => 'crop',
        'r' => 'resize',
        'b' => 'borders',
        'f' => 'fit'
    );
    
    public $width;
    public $height;
    public $file;
    public $action;
    public $source;
    
    public function __construct(Application $app, Request $request, ResizeInterface $resizer = null)
    {
        $this->app = $app;
        $this->request = $request;
        $this->parseRequest();
        $this->source = new File($this->getRealFile($this->file), false);

        if(null === $resizer) {
            $this->resizer = new ThumbnailCreator;
        } else {
            $this->resizer = $resizer;
        }
        $this->resizer->setSource($this->source);
        if(null !== $app['config']->get('general/thumbnails/notfound_image') ) {
            $file = $app['resources']->getPath('app'). '/' .$app['config']->get('general/thumbnails/notfound_image');
            $this->resizer->setDefaultSource(new File($file));
        }
    }
    
    
    public function parseRequest()
    {
        $path = $this->request->getPathInfo();
        preg_match(
            "#/thumbs/(?P<width>[0-9]*)x(?P<height>[0-9]*)(?P<action>[a-z]?)/(?P<file>.*)#", 
            $path, 
            $parsedRequest
        );
        if(!isset($parsedRequest['width']) || !isset($parsedRequest['file'])) {
            return false;
        }
        
        if(isset($parsedRequest['action']) && array_key_exists($parsedRequest['action'], $this->actions)) {
            $this->action = $this->actions[$parsedRequest['action']];
        } else {
            $this->action = 'crop';
        }
        
        $this->width    = $parsedRequest['width'];
        $this->height   = $parsedRequest['height'];
        $this->file     = $parsedRequest['file'];
    }
    
    public function respond()
    {
        $response = $this->createResponse();
        return new Response($response,200, array('Content-Type' => $this->resizer->getSource()->getMimeType()));
    }
    
    public function createResponse()
    {
        $cache = new Cache;
        $cache->setCacheDirectory($this->app['resources']->getPath('cache'));

        $params = array(
            'width'  => $this->width,
            'height' => $this->height  
        );
        $handler = array($this->resizer, $this->action);
        // If the cache exists, this will return it, else, the closure will be called
        // to create this image
        $data = $cache->getOrCreate($this->getCacheKey(), array(), function() use($handler, $params) {
            return call_user_func($handler, $params);
        });
        return $data;
    }
    
    public function getCacheKey()
    {
        $key = join("-", array(str_replace("/", "_", $this->file), $this->width, $this->height, $this->action));
        return $key;
    }
    
    
    public function getRealFile($relativeFile)
    {
        $base = $this->app['resources']->getPath('files');
        return $base . "/" . $relativeFile;
    }
    
    
    
    
  

}




