<?php

namespace Bolt\Thumbs;

use InvalidArgumentException;

/**
 * A value object which has X and Y coordinates.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class Point
{
    /** @var int */
    protected $x;
    /** @var int */
    protected $y;

    /**
     * Point constructor.
     *
     * @param int $x The x-coordinate
     * @param int $y The y-coordinate
     */
    public function __construct($x = 0, $y = 0)
    {
        $this->setX($x);
        $this->setY($y);
    }

    /**
     * Returns the x-coordinate of the point.
     *
     * @return int
     */
    public function getX()
    {
        return $this->x;
    }

    /**
     * Sets the x-coordinate for this point.
     *
     * @param int $x
     *
     * @return Point
     */
    public function setX($x)
    {
        $this->verify($x);
        $this->x = (int) $x;

        return $this;
    }

    /**
     * Returns the y-coordinate of the point.
     *
     * @return int
     */
    public function getY()
    {
        return $this->y;
    }

    /**
     * Sets the x-coordinate for this point.
     *
     * @param int $y
     *
     * @return Point
     */
    public function setY($y)
    {
        $this->verify($y);
        $this->y = (int) $y;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        return sprintf('(%d, %d)', $this->x, $this->y);
    }

    /**
     * Verifies that the coordinate is valid.
     *
     * @param int|mixed $coordinate
     */
    protected function verify($coordinate)
    {
        if (!is_numeric($coordinate)) {
            throw new InvalidArgumentException('Coordinate is expected to be numeric');
        }
        if ($coordinate < 0) {
            throw new InvalidArgumentException('Coordinate is expected to be positive');
        }
    }
}
