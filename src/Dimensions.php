<?php

namespace Bolt\Thumbs;

use InvalidArgumentException;

/**
 * A value object which has a width and a height.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class Dimensions
{
    /** @var int */
    protected $width;
    /** @var int */
    protected $height;

    /**
     * Point constructor.
     *
     * @param int $width  The width
     * @param int $height The height
     */
    public function __construct($width = 0, $height = 0)
    {
        $this->setWidth($width);
        $this->setHeight($height);
    }

    /**
     * Returns the width.
     *
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Sets the width.
     *
     * @param int $width
     *
     * @return Dimensions
     */
    public function setWidth($width)
    {
        $this->verify($width);
        $this->width = (int) $width;

        return $this;
    }

    /**
     * Returns the height.
     *
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Sets the height.
     *
     * @param int $height
     *
     * @return Dimensions
     */
    public function setHeight($height)
    {
        $this->verify($height);
        $this->height = (int) $height;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        return $this->width . 'x' . $this->height;
    }

    /**
     * Verifies that the dimension is valid.
     *
     * @param int|mixed $point
     */
    protected function verify($point)
    {
        if (!is_numeric($point)) {
            throw new InvalidArgumentException('Dimensions point is expected to be numeric');
        }
    }
}
