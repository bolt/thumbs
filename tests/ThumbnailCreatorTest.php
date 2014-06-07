<?php
namespace Bolt\Thumbs\Tests;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\File;

use Bolt\Application;
use Bolt\Configuration\ResourceManager;

use Bolt\Thumbs\ThumbnailCreator;



class ThumbnailCreatorTest extends \PHPUnit_Framework_TestCase
{
    
    public $jpg;
    public $gif;
    public $png;
    
    public function setup()
    {
        $this->jpg = __DIR__."/images/generic-logo.jpg";
        $this->gif = __DIR__."/images/generic-logo.gif";
        $this->png = __DIR__."/images/generic-logo.png";
        require_once __DIR__."/../vendor/bolt/bolt/app/classes/lib.php";
    }
    
    
    public function testSetup()
    {
        $src = new File($this->jpg);
        $creator = new ThumbnailCreator();
        $creator->setSource($src);
        $creator->verify();
        $this->assertEquals($src, $creator->getSource());
    }
    
    public function testFallbacksForBadDimensions()
    {
        $src = new File($this->jpg);
        $creator = new ThumbnailCreator();
        $creator->setSource($src);
        $creator->verify(array('width'=>0,'height'=>-20));
        $this->assertEquals(624, $creator->targetWidth);
        $this->assertEquals(351, $creator->targetHeight);

    }
    
    public function testUpscaling()
    {
        $src = new File($this->jpg);
        $creator = new ThumbnailCreator();
        $creator->setSource($src);
        $creator->allowUpscale = true;
        $creator->verify(array('width'=>800,'height'=>600));
        $this->assertEquals(800, $creator->targetWidth);
        $this->assertEquals(600, $creator->targetHeight);
        
        $creator->allowUpscale = false;
        $creator->verify(array('width'=>800,'height'=>600));
        $this->assertEquals(624, $creator->targetWidth);
        $this->assertEquals(351, $creator->targetHeight);
    }
    
 
    
    
}