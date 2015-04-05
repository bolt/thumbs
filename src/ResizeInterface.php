<?php
namespace Bolt\Thumbs;

use Symfony\Component\HttpFoundation\File\File;

/**
 * Minimum functionality required to support Bolt thumbnail functionality.
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

    /**
     * Sets the original source file
     *
     * @param File $file
     *
     * @return void
     **/
    public function setSource(File $source);

    /**
     * Returns the source file as File object
     *
     * @return File $file
     **/
    public function getSource();

    public function resize($parameters = array());

    public function crop($parameters = array());

    public function border($parameters = array());

    public function fit($parameters = array());
}
