<?php

namespace Bolt\Thumbs;

use Bolt\Filesystem\Image;

/**
 * @author Carson Full <carsonfull@gmail.com>
 */
interface FinderInterface
{
    /**
     * Finds the image based on the given path.
     *
     * @param string $path
     *
     * @return Image
     */
    public function find($path);
}
