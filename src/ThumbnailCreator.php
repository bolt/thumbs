<?php
namespace Bolt\Thumbs;

use Symfony\Component\HttpFoundation\File\File;


class ThumbnailCreator implements ResizeInterface
{
    
    public $source;
    public $defaultSource;
    public $errorSource;
    public $allowUpscale = false;
    
    public $targetWidth;
    public $targetHeight;
    
    
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
     **/
    
    public function verify($parameters = array())
    {
        if(!$this->source->isReadable() && $this->defaultSource) {
            $this->source = $this->defaultSource;
        }
        
        if(isset($parameters['width'])) {
            $this->targetWidth = $parameters['width'];
        }
        
        if(isset($parameters['height'])) {
            $this->targetHeight = $parameters['height'];
        }
        
        
        // Get the original dimensions of the image
        $imageMetrics = getimagesize($this->source->getRealPath());
        
        if(!$imageMetrics) {
            $this->source = $this->errorSource;
        } else {
            $this->originalWidth = $imageMetrics[0];
            $this->originalHeight = $imageMetrics[1];
        }
        
        if(!$this->allowUpscale) {
            if($this->targetWidth > $this->originalWidth) {
                $this->targetWidth = $this->originalWidth;
            }
            if($this->targetHeight > $this->originalHeight) {
                $this->targetHeight = $this->originalHeight;
            }
        }
        
        if(!isset($parameters['width']) || $parameters['width'] < 1) {
            $this->targetWidth = $this->originalWidth;
        }
        
        if(!isset($parameters['height']) || $parameters['height'] < 1) {
            $this->targetHeight = $this->originalHeight;
        }



        
    }
    

    public function resize($parameters = array())
    {
        $this->verify($parameters);
        $data = $this->doResize($this->source->getRealPath(), $this->targetWidth, $this->targetHeight, false);
        if(false !== $data) {
            return $data;
        }
    }
    

    public function crop($parameters = array())
    {
        $this->verify($parameters);
        $data = $this->doResize($this->source->getRealPath(), $this->targetWidth, $this->targetHeight, true);
        if(false !== $data) {
            return $data;
        }
        
    }
    
    public function border($parameters = array())
    {
        $this->verify($parameters);
        $data = $this->doResize($this->source->getRealPath(), $this->targetWidth, $this->targetHeight, false, false, true);
        if(false !== $data) {
            return $data;
        }
    }
    
    public function fit($parameters = array())
    {
        $this->verify($parameters);
        $data = $this->doResize($this->source->getRealPath(), $this->targetWidth, $this->targetHeight, false, false);
        if(false !== $data) {
            return $data;
        }
    }
    
    
    
    protected function doResize($src, $width, $height, $crop=false, $fit = false, $border = false)
    {

        if(!list($w, $h) = getimagesize($src)) return false;

        $type = strtolower(substr(strrchr($src,"."),1));
        if($type == 'jpeg') $type = 'jpg';
        switch($type){
            case 'bmp': $img = imagecreatefromwbmp($src); break;
            case 'gif': $img = imagecreatefromgif($src); break;
            case 'jpg': $img = imagecreatefromjpeg($src); break;
            case 'png': $img = imagecreatefrompng($src); break;
            default : return false;
        }

        if($crop) {
            $ratio = max($width/$w, $height/$h);
            $x = 0;
            $y = 0;
            
            $xratio = $w / $width;
            $yratio = $h / $height;
            
            // calculate x or y coordinate and width or height of source
            if ($xratio > $yratio) {
                $x = round (($w - ($w / $xratio * $yratio)) / 2);
                $w = round ($w / $xratio * $yratio);

            } else if ($yratio > $xratio) {
                $y = round (($h - ($h / $yratio * $xratio)) / 2);
                $h = round ($h / $yratio * $xratio);
            }
            
            
        } elseif(!$border) {
            $ratio = min($width/$w, $height/$h);
            $width = $w * $ratio;
            $height = $h * $ratio;
        } 
        
        $new = imagecreatetruecolor($width, $height);
        
        if($border) {    
            $tmpheight = $h * ($width / $w);
            if ($tmpheight > $height) {
                $width = $w * ($height / $h);
                $x = round(($this->targetWidth - $width) / 2);
            } else {
                $height = $tmpheight;
                $y = round(($this->targetHeight - $height) / 2);
            }

        } 

        // preserve transparency
        if($type == "gif" or $type == "png") {
            imagecolortransparent($new, imagecolorallocatealpha($new, 0, 0, 0, 127));
            imagealphablending($new, false);
            imagesavealpha($new, true);
        }
        
        if(false === $crop && false == $border) {
            imagecopyresampled($new, $img, 0, 0, 0, 0, $width, $height, $w, $h); 
        } elseif($border) {
            imagecopyresampled($new, $img, $x, $y, 0, 0, $width, $height, $w, $h);
        } else {
            imagecopyresampled($new, $img, 0, 0, $x, $y, $width, $height, $w, $h);
        }
        

        ob_start();
        switch($type) {
            case 'bmp': 
                imagewbmp($new); 
                break;
            case 'gif': 
                imagegif($new);  
                break;
            case 'jpg': 
                imagejpeg($new);
                break;
            case 'png': 
                imagepng($new);  
                break;
        }
        $imageData = ob_get_contents();
        ob_end_clean(); 
        if($imageData) {
            return $imageData;
        }
        return false;
    }
    
}