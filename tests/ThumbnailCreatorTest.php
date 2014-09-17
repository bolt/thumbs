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
        require_once __DIR__."/../vendor/bolt/bolt/app/lib.php";
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

        $ok_width = 624;
        $ok_height = 351;

        $testcases = array(
            array(),
            array('width' => $ok_width, 'height' => -20),
            array('width' => $ok_width),
            array('height' => $ok_height),
            array('width' => 'A', 'height' => $ok_height),
            array('width' => 123.456, 'height' => $ok_height),
            array('width' => 'both', 'height' => 'wrong'),
        );

        foreach ($testcases as $parameters) {
            $creator->verify($parameters);
            $this->assertEquals($ok_width, $creator->targetWidth);
            $this->assertEquals($ok_height, $creator->targetHeight);
        }
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


    public function testLandscapeCrop()
    {
        $sample = __DIR__."/images/timthumbs/sample1.jpg";
        $creator = new ThumbnailCreator();
        $creator->setSource(new File($sample));
        $result = $creator->crop(array('width'=>500,'height'=>200));
        $compare = __DIR__."/images/timthumbs/crop_sample1_500_200.jpg";
        file_put_contents(__DIR__."/tmp/test.jpg", $result);
        $this->assertEquals(getimagesize($compare), getimagesize(__DIR__."/tmp/test.jpg"));
        $this->assertEquals(filesize($compare), filesize(__DIR__."/tmp/test.jpg"));
    }

    public function testLandscapeResize()
    {
        $sample = __DIR__."/images/timthumbs/sample1.jpg";
        $creator = new ThumbnailCreator();
        $creator->setSource(new File($sample));
        $result = $creator->resize(array('width'=>500,'height'=>200));
        $compare = __DIR__."/images/timthumbs/resize_sample1_500_200.jpg";
        file_put_contents(__DIR__."/tmp/test.jpg", $result);
        $this->assertEquals(getimagesize($compare), getimagesize(__DIR__."/tmp/test.jpg"));
        $this->assertEquals(filesize($compare), filesize(__DIR__."/tmp/test.jpg") );
    }

    public function testLandscapeFit()
    {
        $sample = __DIR__."/images/timthumbs/sample1.jpg";
        $creator = new ThumbnailCreator();
        $creator->setSource(new File($sample));
        $result = $creator->fit(array('width'=>500,'height'=>200));
        $compare = __DIR__."/images/timthumbs/fit_sample1_500_200.jpg";
        file_put_contents(__DIR__."/tmp/test.jpg", $result);
        $this->assertEquals(getimagesize($compare), getimagesize(__DIR__."/tmp/test.jpg"));
        $this->assertEquals(filesize($compare), filesize(__DIR__."/tmp/test.jpg"));
    }

    public function testLandscapeBorder()
    {
        $sample = __DIR__."/images/timthumbs/sample1.jpg";
        $creator = new ThumbnailCreator();
        $creator->setSource(new File($sample));
        $result = $creator->border(array('width'=>500,'height'=>200));
        $compare = __DIR__."/images/timthumbs/border_sample1_500_200.jpg";
        file_put_contents(__DIR__."/tmp/test.jpg", $result);
        $this->assertEquals( getimagesize($compare), getimagesize(__DIR__."/tmp/test.jpg") );
        $this->assertEquals( filesize($compare) , filesize(__DIR__."/tmp/test.jpg") );
    }

    public function testPortraitCrop()
    {
        $sample = __DIR__."/images/timthumbs/sample2.jpg";
        $creator = new ThumbnailCreator();
        $creator->setSource(new File($sample));
        $result = $creator->crop(array('width'=>200,'height'=>500));
        $compare = __DIR__."/images/timthumbs/crop_sample2_200_500.jpg";
        file_put_contents(__DIR__."/tmp/test.jpg", $result);
        $this->assertEquals(getimagesize($compare), getimagesize(__DIR__."/tmp/test.jpg") );
        $this->assertEquals(filesize($compare), filesize(__DIR__."/tmp/test.jpg") );
    }

    public function testPortraitResize()
    {
        $sample = __DIR__."/images/timthumbs/sample2.jpg";
        $creator = new ThumbnailCreator();
        $creator->setSource(new File($sample));
        $result = $creator->resize(array('width'=>200,'height'=>500));
        $compare = __DIR__."/images/timthumbs/resize_sample2_200_500.jpg";
        file_put_contents(__DIR__."/tmp/test.jpg", $result);
        $this->assertEquals(getimagesize($compare), getimagesize(__DIR__."/tmp/test.jpg"));
        $this->assertEquals(filesize($compare), filesize(__DIR__."/tmp/test.jpg") );
    }

    public function testPortraitFit()
    {
        $sample = __DIR__."/images/timthumbs/sample2.jpg";
        $creator = new ThumbnailCreator();
        $creator->setSource(new File($sample));
        $result = $creator->fit(array('width'=>200,'height'=>500));
        $compare = __DIR__."/images/timthumbs/fit_sample2_200_500.jpg";
        file_put_contents(__DIR__."/tmp/test.jpg", $result);
        $this->assertEquals(getimagesize($compare), getimagesize(__DIR__."/tmp/test.jpg") );
        $this->assertEquals(filesize($compare), filesize(__DIR__."/tmp/test.jpg") );
    }

    public function testPortraitBorder()
    {
        $sample = __DIR__."/images/timthumbs/sample2.jpg";
        $creator = new ThumbnailCreator();
        $creator->setSource(new File($sample));
        $result = $creator->border(array('width'=>200,'height'=>500));
        $compare = __DIR__."/images/timthumbs/border_sample2_200_500.jpg";
        file_put_contents(__DIR__."/tmp/test.jpg", $result);
        $this->assertEquals( getimagesize($compare), getimagesize(__DIR__."/tmp/test.jpg") );
        $this->assertEquals( filesize($compare) , filesize(__DIR__."/tmp/test.jpg") );
    }

    public function tearDown()
    {
        $tmp = __DIR__."/tmp/test.jpg";
        if(is_readable($tmp)) {
            unlink($tmp);
        }

    }



}