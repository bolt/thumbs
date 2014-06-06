<?php
namespace Bolt\Thumbs;
use Symfony\Component\HttpFoundation\File\File;


interface ResizeInterface
{
    
    public function setSource(File $source);
    
    public function resize($parameters = array());
    
    public function crop($parameters = array());

    public function border($parameters = array());
    
    public function fit($parameters = array());


}