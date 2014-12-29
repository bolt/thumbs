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

    public $width;
    public $height;
    public $file;
    public $action;

    public $allowCache = false;
    public $cacheTime = 2592000;

    public $filePaths = array();

    /**
     * Constructor method
     *
     * @param Application $app an instance of Bolt\Application
     * @param Request $request
     * @param ResizeInterface $resizer - uses the built in resizer by default but a custom one can be passed in.
     *
     * @return void
     **/
    public function __construct(Application $app, Request $request, ResizeInterface $resizer = null)
    {
        $this->app = $app;
        $this->request = $request;

        if (null === $resizer) {
            $this->resizer = new ThumbnailCreator;
        } else {
            $this->resizer = $resizer;
        }
    }

    /**
     * Method runs prior to response, loads up config and sets necessary fallbacks.
     *
     * @return void
     **/
    public function initialize()
    {
        if (null !== $this->app['config']->get('general/thumbnails/notfound_image')) {
            $file = $this->app['resources']->getPath('app'). '/' .$this->app['config']->get('general/thumbnails/notfound_image');
            $this->resizer->setDefaultSource(new File($file, false));
        }

        if (null !== $this->app['config']->get('general/thumbnails/error_image')) {
            $file = $this->app['resources']->getPath('app'). '/' .$this->app['config']->get('general/thumbnails/error_image');
            $this->resizer->setErrorSource(new File($file, false));
        }

        if ($this->app['config']->get('general/thumbnails/browser_cache_time')) {
            $this->allowCache = true;
            $this->cacheTime = $this->app['config']->get('general/thumbnails/browser_cache_time');
        }

        if ($this->app['config']->get('general/thumbnails/exif_orientation')) {
            $this->resizer->exifOrientation = $this->app['config']->get('general/thumbnails/exif_orientation');
        }

        if ($this->app['config']->get('general/thumbnails/allow_upscale')) {
            $this->resizer->allowUpscale = $this->app['config']->get('general/thumbnails/allow_upscale');
        }

        if ($this->app['config']->get('general/thumbnails/quality')) {
            $this->resizer->quality = $this->app['config']->get('general/thumbnails/quality', 80);
        }
        $dimensions = $this->app['config']->get('general/thumbnails/default_thumbnail');
        if (is_array($dimensions)) {
            $this->resizer->targetWidth = $dimensions[0];
            $this->resizer->targetHeight = $dimensions[1];
        }

        if (!isset($this->app['thumbnails.paths'])) {
            $this->addPath('files', $this->app['resources']->getPath('files'));
            $this->addPath('theme', $this->app['resources']->getPath('themebase'));
        } else {
            foreach ($this->app['thumbnails.paths'] as $name => $path) {
                $this->addPath($name, $path);
            }
        }

        $this->parseRequest();

        try {
            $this->resizer->setSource(new File($this->getRealFile($this->file)));
        } catch (\Exception $e) {
            $this->resizer->setSource($this->resizer->defaultSource);
        }
    }

    /**
     * Takes the request object and separates into required components. The format is:
     * /thumbs/<width>x<height><command>/<file>
     *
     * @return void
     **/
    public function parseRequest()
    {
        $path = urldecode($this->request->getPathInfo());

        if (preg_match('#/thumbs/(?<width>\d+)x(?<height>\d+)(?<action>[a-z]?)/?(?<file>.+)#', $path, $parsedRequest)) {
            $commands = $this->resizer->provides();
            if (isset($parsedRequest['action']) && array_key_exists($parsedRequest['action'], $commands)) {
                $this->action = $commands[$parsedRequest['action']];
            } else {
                $this->action = 'crop';
            }

            $this->width    = $parsedRequest['width'];
            $this->height   = $parsedRequest['height'];
            $this->file     = $parsedRequest['file'];
        }
    }

    /**
     * Returns a response object based on the type of image requested.
     *
     * @return Response $response
     **/
    public function respond()
    {
        $this->initialize();
        $imageContent = $this->createResponse();
        $this->saveStatic($imageContent);
        $response = isset($this->app['thumbnails.response']) ? $this->app['thumbnails.response'] : new Response;
        $response->setContent($imageContent);
        $response->headers->set('Content-Type', $this->resizer->getSource()->getMimeType());

        if ($this->allowCache) {
            $response->headers->set('Cache-Control', 'max-age=' . $this->cacheTime . ', public');
        }

        return $response;
    }

    /**
     * Delegates to the defined resizer and command to get image data.
     * If the data is cached then this returns directly.
     *
     * @return $data
     **/
    public function createResponse()
    {
        $cache = $this->app['cache'];
        $cache->setNamespace('bolt.thumbs');
        $params = array(
            'width'  => $this->width,
            'height' => $this->height
        );
        $handler = array($this->resizer, $this->action);
        // If the cache exists, this will return it, else, the closure will be called to create this image
        $data = $cache->fetch($this->getCacheKey());
        if ($data) {
            return $data;
        } else {
            $cache->save($this->getCacheKey(), call_user_func($handler, $params));

            return $cache->fetch($this->getCacheKey());
        }
    }

    /**
     * Saves a static copy of the file if the config is set to do so
     *
     * @return void
     **/
    public function saveStatic($imageContent)
    {
        if (!$this->app['config']->get('general/thumbnails/save_files')) {
            return false;
        }
        $path = urldecode($this->request->getPathInfo());
        try {
            $webroot = dirname($this->request->server->get('SCRIPT_FILENAME'));
            $savePath = dirname($webroot.$path);
            if (!is_dir($savePath)) {
                mkdir($savePath, 0777, true);
            }
            file_put_contents($webroot.$path, $imageContent);
        } catch (\Exception $e) {

        }

        return true;
    }

    /**
     * Makes a unique key based on all parameters of the url. This is passed to the caching engine.
     *
     * @return $key
     **/
    public function getCacheKey()
    {
        $key = join('-', array(str_replace('/', '_', $this->file), $this->width, $this->height, $this->action));

        return $key;
    }

    public function addPath($prefix, $path)
    {
        $this->filePaths[$prefix] = $path;
    }

    /**
     * Uses the Bolt application path to return the full path from a relative filename.
     *
     * @param $relativeFile
     * @return string
     **/
    public function getRealFile($relativeFile)
    {
        foreach ($this->filePaths as $prefix => $path) {

            // See if the request includes the path to be used.
            if (strpos($relativeFile, $prefix) === 0) {
                return $path . ltrim($relativeFile, $prefix);
            }

            // Or see if the file actually exists, that'd be fine too.
            if (is_readable($path . '/' . $relativeFile)) {
                return $path . '/' . $relativeFile;
            }

        }

        // Otherwise, we'll have to assume it's in the 'files' folder. Theoretically, we should
        // never get here for an existing image.
        $base = $this->app['resources']->getPath('files');

        return $base . '/' . ltrim($relativeFile, '/');
    }
}
