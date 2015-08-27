<?php
namespace Bolt\Thumbs;

use Bolt\Filesystem\ImageInfo;
use Symfony\Component\HttpFoundation\File\File;

class ThumbnailCreator implements ResizeInterface
{
    /** @var File */
    public $source;
    /** @var File */
    public $defaultSource;
    /** @var File */
    public $errorSource;
    public $allowUpscale = false;
    public $exifOrientation = true;
    public $quality = 80;

    public $targetWidth;
    public $targetHeight;
    public $originalWidth;
    public $originalHeight;

    /** @var Color */
    protected $background;

    public function __construct()
    {
        $this->background = Color::white();
    }

    public function provides()
    {
        return array(
            'c' => 'crop',
            'r' => 'resize',
            'b' => 'border',
            'f' => 'fit'
        );
    }

    public function setSource(File $source)
    {
        $this->source = $source;
    }

    public function getSource()
    {
        return $this->source;
    }

    public function setDefaultSource(File $source)
    {
        $this->defaultSource = $source;
    }

    public function setErrorSource(File $source)
    {
        $this->errorSource = $source;
    }

    /**
     *  This method performs the basic sanity checks before allowing the operation to continue.
     *  If there are any problems with the request it can also reset the source to be one of the
     *  configured fallback images.
     *
     * @param array $parameters
     */
    public function verify($parameters = array())
    {
        if (!$this->source->isReadable() && $this->defaultSource) {
            $this->source = $this->defaultSource;
        }

        // Get the original dimensions of the image
        try {
            $info = ImageInfo::createFromFile($this->source->getRealPath());
        } catch (\Exception $e) {
            $this->source = $this->errorSource;
            try {
                $info = ImageInfo::createFromFile($this->source->getRealPath());
            } catch (\Exception $e) {
                throw new \RuntimeException(
                    'There was an error with the thumbnail image requested and additionally the fallback image could not be displayed.',
                    1
                );
            }
        }
        $this->originalWidth = $info->getWidth();
        $this->originalHeight = $info->getHeight();

        // Set target dimensions to sanitized values
        if (isset($parameters['width']) && preg_match('%^\d+$%', $parameters['width'])) {
            $this->targetWidth = $parameters['width'];
        } else {
            $this->targetWidth = $this->originalWidth;
        }

        if (isset($parameters['height']) && preg_match('%^\d+$%', $parameters['height'])) {
            $this->targetHeight = $parameters['height'];
        } else {
            $this->targetHeight = $this->originalHeight;
        }

        // Autoscaling
        if ($this->targetWidth == 0 and $this->targetHeight == 0) {
            $this->targetWidth = $this->originalWidth;
            $this->targetHeight = $this->originalHeight;
        } elseif ($this->targetWidth == 0) {
            $this->targetWidth = round($this->targetHeight * $this->originalWidth / $this->originalHeight);
        } elseif ($this->targetHeight == 0) {
            $this->targetHeight = round($this->targetWidth * $this->originalHeight / $this->originalWidth);
        }

        // Check for upscale
        if (!$this->allowUpscale) {
            if ($this->targetWidth > $this->originalWidth) {
                $this->targetWidth = $this->originalWidth;
            }
            if ($this->targetHeight > $this->originalHeight) {
                $this->targetHeight = $this->originalHeight;
            }
        }
    }

    public function resize($parameters = array())
    {
        $this->verify($parameters);
        return $this->doResize($this->source->getRealPath(), $this->targetWidth, $this->targetHeight, false);
    }

    public function crop($parameters = array())
    {
        $this->verify($parameters);
        return $this->doResize($this->source->getRealPath(), $this->targetWidth, $this->targetHeight, true);
    }

    public function border($parameters = array())
    {
        $this->verify($parameters);
        return $this->doResize($this->source->getRealPath(), $this->targetWidth, $this->targetHeight, false, false, true);
    }

    public function fit($parameters = array())
    {
        $this->verify($parameters);
        return $this->doResize($this->source->getRealPath(), $this->targetWidth, $this->targetHeight, false, true);
    }

    /**
     * Main resizing function.
     *
     * Since the resizing functionality is almost identical across all actions they all delegate here.
     * Main difference is in plotting the output dimensions where the ratios and position differ slightly.
     *
     * @param string $src
     * @param int    $width
     * @param int    $height
     * @param bool   $crop
     * @param bool   $fit
     * @param bool   $border
     *
     * @return false|string
     */
    protected function doResize($src, $width, $height, $crop = false, $fit = false, $border = false)
    {
        try {
            $img = ImageResource::createFromFile($src);
        } catch (\Exception $e) {
            return false;
        }

        $target = new Dimensions($width, $height);

        $original = $img->getDimensions();

        $point = new Point();

        if ($crop) {
            $xratio = $original->getWidth() / $target->getWidth();
            $yratio = $original->getHeight() / $target->getHeight();

            // calculate x or y coordinate and width or height of source
            if ($xratio > $yratio) {
                $point->setX(round(($original->getWidth() - ($original->getWidth() / $xratio * $yratio)) / 2));
                $original->setWidth(round($original->getWidth() / $xratio * $yratio));
            } elseif ($yratio > $xratio) {
                $point->setY(round(($original->getHeight() - ($original->getHeight() / $yratio * $xratio)) / 2));
                $original->setHeight(round($original->getHeight() / $yratio * $xratio));
            }
        } elseif (!$border && !$fit) {
            $ratio = min($target->getWidth() / $original->getWidth(), $target->getHeight() / $original->getHeight());
            $target->setWidth($original->getWidth() * $ratio);
            $target->setHeight($original->getHeight() * $ratio);
        }

        $new = ImageResource::createNew($target, $img->getType());

        if ($border) {
            $new->fill($this->background);

            $tmpheight = $original->getHeight() * ($target->getWidth() / $original->getWidth());
            if ($tmpheight > $target->getHeight()) {
                $target->setWidth($original->getWidth() * ($target->getHeight() / $original->getHeight()));
                $point->setX(round(($width - $target->getWidth()) / 2));
            } else {
                $target->setHeight($tmpheight);
                $point->setY(round(($height - $target->getHeight()) / 2));
            }
        }

        if (!$crop && !$border) {
            $img->resample(new Point(), new Point(), $target, $original, $new);
        } elseif ($border) {
            $img->resample($point, new Point(), $target, $original, $new);
        } else {
            $img->resample(new Point(), $point, $target, $original, $new);
        }

        return $img->toString();
    }
}
