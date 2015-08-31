<?php

namespace Bolt\Thumbs;

use Bolt\Filesystem\Image;

/**
 * Stores thumbnail data and image file.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class Thumbnail
{
    /** @var Image */
    protected $image;
    /** @var string */
    protected $thumbnail;

    /**
     * Thumbnail constructor.
     *
     * @param Image  $image
     * @param string $thumbnail
     */
    public function __construct(Image $image, $thumbnail)
    {
        $this->image = $image;
        $this->thumbnail = $thumbnail;
    }

    /**
     * @return Image
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @return string
     */
    public function getThumbnail()
    {
        return $this->thumbnail;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->thumbnail;
    }
}
