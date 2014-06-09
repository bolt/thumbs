<?php
namespace Bolt\Thumbs;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Minimum functionality required to support Bolt thumbnail functionality.
 *
 **/
 
interface ResizeInterface
{
    
    /**
     * Specifies an array of command -> method mappings for example the defaults are:
     *     ['c'=>'crop','r'=>'resize','b'=>'border','f'=>'fit']
     * Any others can be specified and if the method is callable they will work.
     * 
     * @return array
     **/
    public function provides();

    public function setSource(File $source);
    
    public function getSource();
    
    public function resize($parameters = array());
    
    public function crop($parameters = array());

    public function border($parameters = array());
    
    public function fit($parameters = array());   

}