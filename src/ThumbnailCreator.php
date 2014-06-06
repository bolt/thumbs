<?php
namespace Bolt\Thumbs;

use Symfony\Component\HttpFoundation\File\File;


class ThumbnailCreator implements ResizeInterface
{
    
    public $source;
    
    
    public function setSource(File $source)
    {
        $this->source = $source;   
    }
    
    public function create($action, $parameters = array())
    {
        if(is_callable(array($this, $action))) {
            $this->$action($parameters);
        }
        throw new \InvalidArgumentException("$action method is not implemented on ".get_class($this));
        
    }
    
    /**
    * Do the image resize
    *
    * @return $output // image content
    */
    public function resize($parameters = array())
    {
        return $this->doResize($this->source->getRealPath(), $parameters['width'], $parameters['height'], false);
            
    }
    

    public function crop($parameters = array())
    {
        return $this->doResize($this->source->getRealPath(), $parameters['width'], $parameters['height'], true);
        
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

        // resize
        if($crop){
            if($w < $width or $h < $height) return false;
            $ratio = max($width/$w, $height/$h);
            $h = $height / $ratio;
            $x = ($w - $width / $ratio) / 2;
            $w = $width / $ratio;
            $xmid = $width/2;
            $ymid = $height/2;
        } else {
            if($w < $width and $h < $height) return false;
            $ratio = min($width/$w, $height/$h);
            $width = $w * $ratio;
            $height = $h * $ratio;
            $x = 0;
        }

        $new = imagecreatetruecolor($width, $height);

        // preserve transparency
        if($type == "gif" or $type == "png"){
            imagecolortransparent($new, imagecolorallocatealpha($new, 0, 0, 0, 127));
            imagealphablending($new, false);
            imagesavealpha($new, true);
        }
        
        if(false === $crop) {
            imagecopyresampled($new, $img, 0, 0, $x, 0, $width, $height, $w, $h); 
       } else {
            imagecopyresampled($new, $img, 0, 0, ($xmid-($width/2)), ($ymid-($height/2)), $width, $height, $w, $h);
       }
        

        ob_start();
        switch($type){
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