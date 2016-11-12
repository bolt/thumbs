<?php

namespace Bolt\Thumbs;

use Bolt\Filesystem\AggregateFilesystemInterface;
use Bolt\Filesystem\Handler\Image;

class Finder implements FinderInterface
{
    /** @var AggregateFilesystemInterface */
    protected $filesystem;
    /** @var string[] */
    protected $filesystemsToCheck;
    /** @var Image */
    protected $defaultImage;

    /**
     * Finder constructor.
     *
     * @param AggregateFilesystemInterface $filesystem
     * @param string[]                     $filesystemsToCheck
     * @param Image                        $defaultImage
     */
    public function __construct(
        AggregateFilesystemInterface $filesystem,
        array $filesystemsToCheck,
        Image $defaultImage
    ) {
        $this->filesystem = $filesystem;
        $this->filesystemsToCheck = $filesystemsToCheck;
        $this->defaultImage = $defaultImage;
    }

    /**
     * Searches the filesystem for the image based on the path,
     * or returns the default image if not found.
     *
     * @param string $path
     *
     * @return Image
     */
    public function find($path)
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
}
