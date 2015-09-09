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
    /** @var Filesystem\AggregateFilesystemInterface */
    protected $filesystem;
    /** @var string[] */
    protected $filesystemsToCheck;
    /** @var Filesystem\Image */
    protected $defaultImage;
    /** @var Filesystem\Image */
    protected $errorImage;

    /** @var Filesystem\FilesystemInterface|null */
    protected $webFs;
    /** @var Cache */
    protected $cache;
    /** @var int|null */
    protected $cacheTime;

    /** @var bool */
    protected $allowUpscale;

    /**
     * ThumbnailResponder constructor.
     *
     * @param ThumbnailCreatorInterface               $thumbnailCreator
     * @param Filesystem\AggregateFilesystemInterface $filesystem
     * @param array                                   $filesystemsToCheck
     * @param Filesystem\Image                        $defaultImage
     * @param Filesystem\Image                        $errorImage
     * @param Filesystem\FilesystemInterface|null     $webFs
     * @param Cache                                   $cache
     * @param int                                     $cacheTime
     * @param bool                                    $allowUpscale
     */
    public function __construct(
        ThumbnailCreatorInterface $thumbnailCreator,
        Filesystem\AggregateFilesystemInterface $filesystem,
        array $filesystemsToCheck,
        Filesystem\Image $defaultImage,
        Filesystem\Image $errorImage,
        Filesystem\FilesystemInterface $webFs = null,
        Cache $cache = null,
        $cacheTime = 0,
        $allowUpscale = false
    ) {
        $this->thumbnailCreator = $thumbnailCreator;
        $this->filesystem = $filesystem;
        $this->filesystemsToCheck = $filesystemsToCheck;
        $this->defaultImage = $defaultImage;
        $this->errorImage = $errorImage;

        $this->webFs = $webFs;
        $this->cache = $cache;
        $this->cacheTime = $cacheTime;

        $this->allowUpscale = $allowUpscale;
    }

    /**
     * Respond to the thumbnail request.
     *
     * @param string     $requestPath
     * @param string     $path
     * @param string     $action
     * @param Dimensions $dimensions
     *
     * @return Thumbnail
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

        // Return thumbnail
        return new Thumbnail($image, $thumbnail);
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
        if ($this->cache === null) {
            return $this->thumbnailCreator->create($transaction);
        }

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
        if ($this->webFs === null) {
            return;
        }
        try {
            $this->webFs->write($requestPath, $imageContent);
        } catch (Exception $e) {
        }
    }
}
