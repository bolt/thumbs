<?php

namespace Bolt\Thumbs;

use InvalidArgumentException;

/**
 * An object representation of a color.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class Color
{
    /** @var int|null */
    protected $index;
    /** @var int */
    protected $red;
    /** @var int */
    protected $green;
    /** @var int */
    protected $blue;
    /** @var int|null */
    protected $alpha;

    /**
     * Color constructor.
     *
     * @param int      $red   Value of red component (between 0 and 255)
     * @param int      $green Value of green component (between 0 and 255)
     * @param int      $blue  Value of blue component (between 0 and 255)
     * @param int|null $alpha Optional value of alpha component (between 0 and 127). 0 = opaque, 127 = transparent.
     * @param int|null $index Index of the color for the image resource
     */
    public function __construct($red, $green, $blue, $alpha = null, $index = null)
    {
        foreach ([$red, $green, $blue] as $component) {
            if (!is_numeric($component)) {
                throw new InvalidArgumentException('Color components are expected to be numeric');
            }
            if ($component < 0 || $component > 255) {
                throw new InvalidArgumentException('Color components are expected to be between 0 and 255');
            }
        }
        if ($alpha !== null) {
            if (!is_numeric($alpha)) {
                throw new InvalidArgumentException('Color alpha component is expected to be numeric');
            }
            if ($alpha < 0 || $alpha > 127) {
                throw new InvalidArgumentException('Color alpha component is expected to be between 0 and 127');
            }
        }

        $this->red = $red;
        $this->green = $green;
        $this->blue = $blue;
        $this->alpha = $alpha;
        $this->index = $index;
    }

    /**
     * Shortcut to create a transparent color.
     *
     * @return Color
     */
    public static function transparent()
    {
        return new static(0, 0, 0, 127);
    }

    /**
     * Shortcut to create a white color.
     *
     * @return Color
     */
    public static function white()
    {
        return new static(255, 255, 255);
    }

    /**
     * @return int
     */
    public function getRed()
    {
        return $this->red;
    }

    /**
     * @return int
     */
    public function getGreen()
    {
        return $this->green;
    }

    /**
     * @return int
     */
    public function getBlue()
    {
        return $this->blue;
    }

    /**
     * @return int|null
     */
    public function getAlpha()
    {
        return $this->alpha;
    }

    /**
     * @return int|null
     */
    public function getIndex()
    {
        return $this->index;
    }
}
