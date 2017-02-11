<?php

namespace Bolt\Thumbs;

use Bolt\Filesystem;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\VoidCache;
use Exception;

/**
 * Responder is responsible for processing the transaction.
 * It invokes the finder and creator, and handles the caching logic.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class Responder implements ResponderInterface
{
    /** @var CreatorInterface */
    protected $creator;
    /** @var FinderInterface */
    protected $finder;
    /** @var Filesystem\Handler\Image */
    protected $errorImage;

    /** @var Filesystem\FilesystemInterface|null */
    protected $webFs;
    /** @var Cache */
    protected $cache;
    /** @var int|null */
    protected $cacheTime;

    /**
     * Responder constructor.
     *
     * @param CreatorInterface                    $creator
     * @param FinderInterface                     $finder
     * @param Filesystem\Handler\Image            $errorImage
     * @param Filesystem\FilesystemInterface|null $webFs
     * @param Cache                               $cache
     * @param int                                 $cacheTime
     */
    public function __construct(
        CreatorInterface $creator,
        FinderInterface $finder,
        Filesystem\Handler\Image $errorImage,
        Filesystem\FilesystemInterface $webFs = null,
        Cache $cache = null,
        $cacheTime = 0
    ) {
        $this->creator = $creator;
        $this->finder = $finder;
        $this->errorImage = $errorImage;

        $this->webFs = $webFs;
        $this->cache = $cache ?: new VoidCache();
        $this->cacheTime = $cacheTime;
    }

    /**
     * {@inheritdoc}
     */
    public function respond(Transaction $transaction)
    {
        $transaction->setErrorImage($this->errorImage);

        $image = $this->finder->find($transaction->getFilePath());
        $transaction->setSrcImage($image);

        // Get the thumbnail from cache or create it
        $thumbnail = $this->getThumbnail($transaction);

        // Save static copy if enabled
        $this->saveStaticThumbnail($transaction->getRequestPath(), $thumbnail);

        // Return thumbnail
        return new Thumbnail($transaction->getSrcImage(), $thumbnail);
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

        $imageData = $this->creator->create($transaction);

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
        if ($this->webFs === null || $requestPath === null) {
            return;
        }
        try {
            $this->webFs->write($requestPath, $imageContent);
        } catch (Exception $e) {
        }
    }
}
