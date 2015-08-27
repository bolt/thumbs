<?php

namespace Bolt\Thumbs;

use Bolt\Filesystem;
use Doctrine\Common\Cache\Cache;
use Exception;

/**
 * This contains the Business Logic for creating thumbnails.
 * It joins the filesystems, caches, thumbnail creator, and all the options.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class ThumbnailResponder
{
    /** @var ThumbnailCreatorInterface */
    protected $thumbnailCreator;
    /** @var Filesystem\Manager */
    protected $filesystem;
    /** @var string[] */
    protected $filesystemsToCheck;
    /** @var Cache */
    protected $cache;
    /** @var Filesystem\FilesystemInterface */
    protected $webFs;

    /** @var Filesystem\Image */
    protected $defaultImage;
    /** @var Filesystem\Image */
    protected $errorImage;
    /** @var int|null */
    protected $cacheTime;
    /** @var bool */
    protected $allowUpscale = false;
    /** @var bool */
    protected $saveFiles = false;

    /**
     * ThumbnailResponder constructor.
     *
     * @param ThumbnailCreatorInterface      $thumbnailCreator
     * @param Filesystem\Manager             $filesystem
     * @param array                          $filesystemsToCheck
     * @param Cache                          $cache
     * @param Filesystem\FilesystemInterface $webFs
     * @param Filesystem\Image               $errorImage
     * @param Filesystem\Image               $defaultImage
     * @param int|null                       $cacheTime
     * @param bool                           $allowUpscale
     * @param bool                           $saveFiles
     */
    public function __construct(
        ThumbnailCreatorInterface $thumbnailCreator,
        Filesystem\Manager $filesystem,
        array $filesystemsToCheck,
        Cache $cache,
        Filesystem\FilesystemInterface $webFs,
        Filesystem\Image $defaultImage,
        Filesystem\Image $errorImage,
        $cacheTime,
        $allowUpscale,
        $saveFiles
    ) {
        $this->thumbnailCreator = $thumbnailCreator;
        $this->filesystem = $filesystem;
        $this->filesystemsToCheck = $filesystemsToCheck;
        $this->cache = $cache;
        $this->webFs = $webFs;

        $this->defaultImage = $defaultImage;
        $this->errorImage = $errorImage;
        $this->cacheTime = $cacheTime;
        $this->allowUpscale = $allowUpscale;
        $this->saveFiles = $saveFiles;
    }

    /**
     * Respond to the thumbnail request.
     *
     * @param string     $requestPath
     * @param string     $path
     * @param string     $action
     * @param Dimensions $dimensions
     *
     * @return Response
     */
    public function respond($requestPath, $path, $action, Dimensions $dimensions)
    {
        // Create a transaction with the global options
        $transaction = new Transaction();
        $transaction->setErrorImage($this->errorImage);
        $transaction->setAllowUpscale($this->allowUpscale);
        $transaction->setBackground(Color::white());

        // Set properties for this thumbnail request
        $transaction->setTarget($dimensions);
        $transaction->setAction($action);

        $image = $this->findImage($path);
        $transaction->setSrcImage($image);

        // Get the thumbnail from cache or create it
        $thumbnail = $this->getThumbnail($transaction);

        // Save static copy if enabled
        $this->saveStaticThumbnail($requestPath, $thumbnail);

        // Return thumbnail response
        $response = new Response($thumbnail, $image->getInfo());
        if ($this->cacheTime > 0) {
            $response->setPublic()->setMaxAge($this->cacheTime);
        }
        return $response;
    }

    /**
     * Searches the filesystem for the image based on the path,
     * or returns the default image if not found.
     *
     * @param string $path
     *
     * @return Filesystem\Image
     */
    protected function findImage($path)
    {
        foreach ($this->filesystemsToCheck as $prefix) {
            /** @var Filesystem\FilesystemInterface $fs */
            $fs = $this->filesystem->getFilesystem($prefix);
            $image = $fs->getImage($path);
            if ($image->exists()) {
                return $image;
            }
        }

        return $this->defaultImage;
    }

    /**
     * Returns thumbnail data for the given transaction.
     *
     * Handles the cache layer around the creation as well.
     *
     * @param Transaction $transaction
     *
     * @return string
     */
    protected function getThumbnail(Transaction $transaction)
    {
        $cacheKey = $transaction->getHash();
        if ($this->cache->contains($cacheKey)) {
            return $this->cache->fetch($cacheKey);
        }

        $imageData = $this->thumbnailCreator->create($transaction);

        $this->cache->save($cacheKey, $imageData, $this->cacheTime);

        return $imageData;
    }

    /**
     * Saves a static copy of the thumbnail to the web folder.
     *
     * @param string $requestPath
     * @param string $imageContent
     */
    protected function saveStaticThumbnail($requestPath, $imageContent)
    {
        if (!$this->saveFiles) {
            return;
        }
        try {
            $this->webFs->write($requestPath, $imageContent);
        } catch (Exception $e) {
        }
    }
}
