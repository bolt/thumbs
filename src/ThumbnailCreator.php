<?php
namespace Bolt\Thumbs;

use Symfony\Component\HttpFoundation\File\File;

class ThumbnailCreator implements ResizeInterface
{
    public $source;
    public $defaultSource;
    public $errorSource;
    public $allowUpscale = false;
    public $exifOrientation = true;
    public $quality = 80;
    public $canvas  = array(255, 255, 255);

    public $targetWidth;
    public $targetHeight;

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
     **/
    public function verify($parameters = array())
    {
        if (!$this->source->isReadable() && $this->defaultSource) {
            $this->source = $this->defaultSource;
        }

        // Get the original dimensions of the image
        $imageMetrics = @getimagesize($this->source->getRealPath());

        if (!$imageMetrics) {
            $this->source = $this->errorSource;
            $imageMetrics = @getimagesize($this->source->getRealPath());
            if (!$imageMetrics) {
                throw new \RuntimeException(
                    'There was an error with the thumbnail image requested and additionally the fallback image could not be displayed.',
                    1
                );
            }
        }
        $this->originalWidth = $imageMetrics[0];
        $this->originalHeight = $imageMetrics[1];

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
        $data = $this->doResize($this->source->getRealPath(), $this->targetWidth, $this->targetHeight, false);
        if (false !== $data) {
            return $data;
        }
    }

    public function crop($parameters = array())
    {
        $this->verify($parameters);
        $data = $this->doResize($this->source->getRealPath(), $this->targetWidth, $this->targetHeight, true);
        if (false !== $data) {
            return $data;
        }

    }

    public function border($parameters = array())
    {
        $this->verify($parameters);
        $data = $this->doResize($this->source->getRealPath(), $this->targetWidth, $this->targetHeight, false, false, true);
        if (false !== $data) {
            return $data;
        }
    }

    public function fit($parameters = array())
    {
        $this->verify($parameters);
        $data = $this->doResize($this->source->getRealPath(), $this->targetWidth, $this->targetHeight, false, true);
        if (false !== $data) {
            return $data;
        }
    }

    /**
     * Main resizing function.
     *
     * Since the resizing functionality is almost identical across all actions they all delegate here.
     * Main difference is in plotting the output dimensions where the ratios and position differ slightly.
     *
     * @return $imageData
     **/
    protected function doResize($src, $width, $height, $crop = false, $fit = false, $border = false)
    {
        if (!list($w, $h) = getimagesize($src)) {
            return false;
        }

        $type = strtolower(substr(strrchr($src, '.'), 1));
        if ($type == 'jpeg') {
            $type = 'jpg';
        }

        switch($type)
        {
            case 'bmp':
                $img = imagecreatefromwbmp($src);
                break;
            case 'gif':
                $img = imagecreatefromgif($src);
                break;
            case 'jpg':
                $exif = $this->exifOrientation ? exif_read_data($src) : false;
                if ($exif !== false && isset($exif['Orientation'])) {
                    $img = self::imageCreateFromJpegExif($src);
                    $w = imagesx($img);
                    $h = imagesy($img);
                } else {
                    $img = imagecreatefromjpeg($src);
                }
                break;
            case 'png':
                $img = imagecreatefrompng($src);
                break;
            default:
                return false;
        }

        if ($crop) {
            $ratio = max($width / $w, $height / $h);
            $x = 0;
            $y = 0;

            $xratio = $w / $width;
            $yratio = $h / $height;

            // calculate x or y coordinate and width or height of source
            if ($xratio > $yratio) {
                $x = round(($w - ($w / $xratio * $yratio)) / 2);
                $w = round($w / $xratio * $yratio);

            } elseif ($yratio > $xratio) {
                $y = round(($h - ($h / $yratio * $xratio)) / 2);
                $h = round($h / $yratio * $xratio);
            }


        } elseif (!$border && !$fit) {
            $ratio = min($width / $w, $height / $h);
            $width = $w * $ratio;
            $height = $h * $ratio;
        }

        $new = imagecreatetruecolor($width, $height);

        if ($border) {

            if (count($this->canvas) == 3) {
                $canvas = imagecolorallocate($new, $this->canvas[0], $this->canvas[1], $this->canvas[2]);
                imagefill($new, 0, 0, $canvas);
            }

            $x = 0;
            $y = 0;
            $tmpheight = $h * ($width / $w);
            if ($tmpheight > $height) {
                $width = $w * ($height / $h);
                $x = round(($this->targetWidth - $width) / 2);
            } else {
                $height = $tmpheight;
                $y = round(($this->targetHeight - $height) / 2);
            }

        }

        // Preserve transparency where available

        if ($type == 'gif' or $type == 'png') {
            imagecolortransparent($new, imagecolorallocatealpha($new, 0, 0, 0, 127));
            imagealphablending($new, false);
            imagesavealpha($new, true);
        }

        if (false === $crop && false === $border) {
            imagecopyresampled($new, $img, 0, 0, 0, 0, $width, $height, $w, $h);
        } elseif ($border) {
            imagecopyresampled($new, $img, $x, $y, 0, 0, $width, $height, $w, $h);
        } else {
            imagecopyresampled($new, $img, 0, 0, $x, $y, $width, $height, $w, $h);
        }

        return $this->getOutput($new, $type);
    }

    /**
     * undocumented function
     *
     * @param $imageContent an image resource
     * @param $type one of bmp|gif|jpg|png
     * @return $imageData | false
     **/
    protected function getOutput($imageContent, $type)
    {
        // This block captures the image data, since these image commands echo out the data
        // we wrap the operation in output buffering to capture the data as a string.
        ob_start();
        switch($type) {
            case 'bmp':
                imagewbmp($imageContent);
                break;
            case 'gif':
                imagegif($imageContent);
                break;
            case 'jpg':
                imagejpeg($imageContent, null, $this->quality);
                break;
            case 'png':
                imagepng($imageContent);
                break;
        }
        $imageData = ob_get_contents();
        ob_end_clean();

        if ($imageData) {
            return $imageData;
        }

        return false;
    }

    /**
     * Create image from jpeg with EXIF orientation
     *
     * This function is the same as imagecreatefromjpeg except it will take into account
     * the EXIF orientation in the file
     *
     * @param $src the path to the file
     **/
    public static function imageCreateFromJpegExif($src)
    {
        $img = imagecreatefromjpeg($src);
        $exif = exif_read_data($src);
        $ort = $exif['Orientation'];
        switch($ort)
        {
            case 2: // horizontal flip
                $img = self::imageFlip($img, 1);
                break;
            case 3: // 180 rotate left
                $img = imagerotate($img, 180, 0);
                break;
            case 4: // vertical flip
                $img = self::imageFlip($img, 0);
                break;
            case 5: // vertical flip + 90 rotate right
                $img = self::imageFlip($img, 0);
                $img = imagerotate($img, -90, 0);
                break;
            case 6: // 90 rotate right
                $img = imagerotate($img, -90, 0);
                break;
            case 7: // horizontal flip + 90 rotate right
                $img = self::imageFlip($img, 1);
                $img = imagerotate($img, -90, 0);
                break;
            case 8: // 90 rotate left
                $img = imagerotate($img, 90, 0);
                break;
        }

        return $img;
    }

    /**
     * Image Flip
     *
     * Based on http://stackoverflow.com/a/10001884/1136593
     * Thanks Jon Grant
     *
     * @param $imgsrc (image to flip)
     * @param $mode (0 = vertical, 1 = horizontal, 2 = both) - defaults to vertical flip
     *
     */
    public static function imageFlip($imgsrc, $mode = 0)
    {
        $width = imagesx($imgsrc);
        $height = imagesy($imgsrc);

        $src_x = 0;
        $src_y = 0;
        $src_width = $width;
        $src_height = $height;

        switch ( $mode )
        {
            default:
            case '0': //vertical
                $src_y = $height -1;
                $src_height= -$height;
                break;
            case '1': //horizontal
                $src_x = $width -1;
                $src_width = -$width;
                break;
            case '2': //both
                $src_x = $width -1;
                $src_y = $height -1;
                $src_width = -$width;
                $src_height = -$height;
                break;
        }

        $imgdest = imagecreatetruecolor ( $width, $height );

        if (imagecopyresampled($imgdest, $imgsrc, 0, 0, $src_x, $src_y , $width, $height, $src_width, $src_height))
        {
            return $imgdest;
        }

        return $imgsrc;
    }
}
