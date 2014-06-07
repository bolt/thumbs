<?php
namespace Bolt\Thumbs;

use Symfony\Component\HttpFoundation\File\File;


class ThumbnailCreator implements ResizeInterface
{
    
    public $source;
    public $defaultSource;
    public $errorSource;
    public $allowUpscale = false;
    
    
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


        
    }
    
    /**
    * Do the image resize
    *
    * @return $output // image content
    */
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
        
    }
    
    public function fit($parameters = array())
    {
        
    }
    
    
    protected function doResize($src, $width, $height, $crop=false)
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
            
            
        } else {
            $ratio = min($width/$w, $height/$h);
            $width = $w * $ratio;
            $height = $h * $ratio;
        }

        $new = imagecreatetruecolor($width, $height);

        // preserve transparency
        if($type == "gif" or $type == "png") {
            imagecolortransparent($new, imagecolorallocatealpha($new, 0, 0, 0, 127));
            imagealphablending($new, false);
            imagesavealpha($new, true);
        }
        
        if(false === $crop) {
            imagecopyresampled($new, $img, 0, 0, 0, 0, $width, $height, $w, $h); 
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