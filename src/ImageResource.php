<?php

namespace Bolt\Thumbs;

use Bolt\Filesystem\ImageInfo;
use InvalidArgumentException;
use PHPExif\Exif;

/**
 * An object representation of GD's native image resources.
 *
 * Note: Not all methods are included in this class yet.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class ImageResource
{
    /** @var resource */
    protected $resource;
    /** @var int */
    protected $type;
    /** @var ImageInfo */
    protected $info;

    /**
     * ImageResource constructor.
     *
     * Either type or info need to be provided.
     *
     * @param resource  $resource A GD resource
     * @param int       $type     Type of image as a IMAGETYPE_* constant
     * @param ImageInfo $info     Image info.
     */
    public function __construct($resource, $type = null, ImageInfo $info = null)
    {
        if (!is_resource($resource) || !get_resource_type($resource) === 'gd') {
            throw new InvalidArgumentException('Given resource must be a GD resource');
        }
        $this->resource = $resource;
        $this->type = $type;
        $this->info = $info;

        if ($info) {
            $this->type = $info->getType();
        }
        if ($this->type === null) {
            throw new InvalidArgumentException('Type or ImageInfo need to be provided');
        }

        if ($this->type === IMAGETYPE_JPEG) {
            $this->normalizeJpegOrientation();
        }
    }

    /**
     * Creates an ImageResource from a file.
     *
     * @param string $file A filepath
     *
     * @return ImageResource
     */
    public static function createFromFile($file)
    {
        $info = ImageInfo::createFromFile($file);
        switch ($info->getType()) {
            case IMAGETYPE_BMP:
                $resource = imagecreatefromwbmp($file);
                break;
            case IMAGETYPE_GIF:
                $resource = imagecreatefromgif($file);
                break;
            case IMAGETYPE_JPEG:
                $resource = imagecreatefromjpeg($file);
                break;
            case IMAGETYPE_PNG:
                $resource = imagecreatefrompng($file);
                break;
            default:
                throw new InvalidArgumentException('Unknown image file');
        }

        return new static($resource, null, $info);
    }

    /**
     * Creates an ImageResource from a string of image data.
     *
     * @param string $data A string containing the image data
     *
     * @return ImageResource
     */
    public static function createFromString($data)
    {
        $info = ImageInfo::createFromString($data);
        $resource = imagecreatefromstring($data);
        return new static($resource, null, $info);
    }

    /**
     * Creates a new image given the width and height.
     *
     * @param Dimensions $dimensions Image dimensions
     * @param int        $type       Type of image as a IMAGETYPE_* constant
     *
     * @return ImageResource
     */
    public static function createNew(Dimensions $dimensions, $type)
    {
        $resource = imagecreatetruecolor($dimensions->getWidth(), $dimensions->getHeight());
        if ($resource === false) {
            throw new InvalidArgumentException('Failed to create new image');
        }

        // Preserve transparency
        imagealphablending($resource, false);
        imagesavealpha($resource, true);

        return new static($resource, $type);
    }

    /**
     * Returns the GD resource
     *
     * @return resource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Returns the image dimensions.
     *
     * @return Dimensions
     */
    public function getDimensions()
    {
        return new Dimensions(imagesx($this->resource), imagesy($this->resource));
    }

    /**
     * Returns the image type as a IMAGETYPE_* constant.
     *
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Returns the image's info.
     *
     * @return ImageInfo
     */
    public function getInfo()
    {
        if (!$this->info) {
            $this->info = ImageInfo::createFromString($this);
        }
        return $this->info;
    }

    /**
     * @return Exif
     */
    public function getExif()
    {
        return $this->getInfo()->getExif();
    }

    /**
     * Resize part of an image with resampling.
     *
     * @param Point         $destPoint      The destination point
     * @param Point         $srcPoint       The source point
     * @param Dimensions    $destDimensions The destination dimensions
     * @param Dimensions    $srcDimensions  The source dimensions
     * @param ImageResource $dest           Optional destination image. Default is current image.
     *
     * @return ImageResource This image
     */
    public function resample(
        Point $destPoint,
        Point $srcPoint,
        Dimensions $destDimensions,
        Dimensions $srcDimensions,
        ImageResource $dest = null
    ) {
        $dest = $dest ?: clone $this;

        imagecopyresampled(
            $dest->resource,
            $this->resource,
            $destPoint->getX(),
            $destPoint->getY(),
            $srcPoint->getX(),
            $srcPoint->getY(),
            $destDimensions->getWidth(),
            $destDimensions->getHeight(),
            $srcDimensions->getWidth(),
            $srcDimensions->getHeight()
        );
        $this->resource = $dest->resource;
        $this->resetInfo();

        return $this;
    }

    /**
     * Flips the image.
     *
     * Based on http://stackoverflow.com/a/10001884/1136593
     * Thanks Jon Grant
     *
     * @param string $mode ('V' = vertical, 'H' = horizontal, 'HV' = both)
     *
     * @return ImageResource This image
     */
    public function flip($mode)
    {
        $dim = $this->getDimensions();

        $srcPoint = new Point();
        $srcDim = clone $dim;

        // Flip vertically
        if (stripos($mode, 'V') !== false) {
            $srcPoint->setY($dim->getHeight() - 1);
            $srcDim->setHeight(-$dim->getHeight());
        }

        // Flip horizontally
        if (stripos($mode, 'H') !== false) {
            $srcPoint->setX($dim->getWidth() - 1);
            $srcDim->setWidth(-$dim->getWidth());
        }

        $this->resample(new Point(), $srcPoint, $dim, $srcDim);

        return $this;
    }

    /**
     * Rotates the image.
     *
     * @param string $angle ('L' = -90°, 'R' = +90°, 'T' = 180°)
     *
     * @return ImageResource This image
     */
    public function rotate($angle)
    {
        $rotate = [
            'L' => 270,
            'R' => 90,
            'T' => 180,
        ];

        if (!isset($rotate[$angle])) {
            return $this;
        }

        $this->resource = imagerotate($this->resource, $rotate[$angle], 0);
        $this->resetInfo();

        return $this;
    }

    /**
     * Writes the image to a file.
     *
     * @param string $file
     */
    public function toFile($file)
    {
        switch($this->type) {
            case IMAGETYPE_BMP:
                imagewbmp($this->resource, $file);
                break;
            case IMAGETYPE_GIF:
                imagegif($this->resource, $file);
                break;
            case IMAGETYPE_JPEG:
                imageinterlace($this->resource, 1);
                imagejpeg($this->resource, $file);
                break;
            case IMAGETYPE_PNG:
                imagepng($this->resource, $file);
                break;
            default:
                throw new \RuntimeException('Unknown image type');
        }
    }

    /**
     * Returns the image as a data string.
     *
     * @return string
     */
    public function toString()
    {
        ob_start();

        try {
            $this->toFile(null);
        } catch (\RuntimeException $e) {
            ob_end_clean();
            throw $e;
        }

        $data = ob_get_contents();
        ob_end_clean();

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        try {
            return $this->toString();
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * @inheritDoc
     */
    public function __clone()
    {
        $original = $this->resource;

        $dim = $this->getDimensions();
        $copy = static::createNew($dim, $this->getType());
        imagecopy($copy->resource, $original, 0, 0, 0, 0, $dim->getWidth(), $dim->getHeight());

        $this->resource = $copy->resource;

        $this->resetInfo();
    }

    /**
     * @inheritDoc
     */
    public function __destroy()
    {
        imagedestroy($this->resource);
    }

    /**
     * If orientation in EXIF data is not normal,
     * flip and/or rotate image until it is correct
     */
    protected function normalizeJpegOrientation()
    {
        $orientation = $this->getExif()->getOrientation();
        $modes = [2 => 'H-', 3 => '-T', 4 => 'V-', 5 => 'VL', 6 => '-L', 7 => 'HL', 8 => '-R'];
        if (!isset($modes[$orientation])) {
            return;
        }
        $mode = $modes[$orientation];

        $this->flip($mode[0])->rotate($mode[1]);
    }

    /**
     * If image changes, info needs to be recreated
     */
    protected function resetInfo()
    {
        $this->info = null;
    }
}
