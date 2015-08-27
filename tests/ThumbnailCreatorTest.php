<?php
namespace Bolt\Thumbs\Tests;

use Bolt\Thumbs\ThumbnailCreator;
use Symfony\Component\HttpFoundation\File\File;

class ThumbnailCreatorTest extends \PHPUnit_Framework_TestCase
{
    public $jpg;
    public $gif;
    public $png;

    public function setup()
    {
        @mkdir(__DIR__ . '/tmp', 0777, true);
        $this->jpg = __DIR__ . '/images/generic-logo.jpg';
        $this->gif = __DIR__ . '/images/generic-logo.gif';
        $this->png = __DIR__ . '/images/generic-logo.png';
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

        $okWidth = 624;
        $okHeight = 351;

        $testcases = array(
            array(),
            array('width'  => $okWidth, 'height' => -20),
            array('width'  => $okWidth),
            array('height' => $okHeight),
            array('width'  => 'A', 'height' => $okHeight),
            array('width'  => 123.456, 'height' => $okHeight),
            array('width'  => 'both', 'height' => 'wrong'),
        );

        foreach ($testcases as $parameters) {
            $creator->verify($parameters);
            $this->assertEquals($okWidth, $creator->targetWidth);
            $this->assertEquals($okHeight, $creator->targetHeight);
        }
    }

    public function testFallbacksForHorizontalAutoscale()
    {
        $sample = __DIR__ . '/images/samples/sample2.jpg';  // 427x640
        $creator = new ThumbnailCreator();
        $creator->setSource(new File($sample));

        $result = $creator->crop(array('width' => 0, 'height' => 320));
        file_put_contents(__DIR__ . '/tmp/test.jpg', $result);
        list($width, $height) = getimagesize(__DIR__ . '/tmp/test.jpg');
        $this->assertEquals($width, 214);
        $this->assertEquals($height, 320);
    }

    public function testFallbacksForVerticalAutoscale()
    {
        $sample = __DIR__ . '/images/samples/sample1.jpg';  // 1000x667
        $creator = new ThumbnailCreator();
        $creator->setSource(new File($sample));

        $result = $creator->crop(array('width' => 500, 'height' => 0));
        file_put_contents(__DIR__ . '/tmp/test.jpg', $result);
        list($width, $height) = getimagesize(__DIR__ . '/tmp/test.jpg');
        $this->assertEquals($width, 500);
        $this->assertEquals($height, 334);
    }

    public function testUpscaling()
    {
        $src = new File($this->jpg);
        $creator = new ThumbnailCreator();
        $creator->setSource($src);
        $creator->allowUpscale = true;
        $creator->verify(array('width' => 800, 'height' => 600));
        $this->assertEquals(800, $creator->targetWidth);
        $this->assertEquals(600, $creator->targetHeight);

        $creator->allowUpscale = false;
        $creator->verify(array('width' => 800, 'height' => 600));
        $this->assertEquals(624, $creator->targetWidth);
        $this->assertEquals(351, $creator->targetHeight);
    }

    public function testLandscapeCrop()
    {
        $sample = __DIR__ . '/images/samples/sample1.jpg';
        $creator = new ThumbnailCreator();
        $creator->setSource(new File($sample));
        $result = $creator->crop(array('width' => 500, 'height' => 200));
        $compare = __DIR__ . '/images/compare/crop_sample1_500_200.jpg';
        file_put_contents(__DIR__ . '/tmp/test.jpg', $result);
        $this->assertEquals(getimagesize($compare), getimagesize(__DIR__ . '/tmp/test.jpg'));
    }

    public function testLandscapeResize()
    {
        $sample = __DIR__ . '/images/samples/sample1.jpg';
        $creator = new ThumbnailCreator();
        $creator->setSource(new File($sample));
        $result = $creator->resize(array('width' => 500, 'height' => 200));
        $compare = __DIR__ . '/images/compare/resize_sample1_500_200.jpg';
        file_put_contents(__DIR__ . '/tmp/test.jpg', $result);
        $this->assertEquals(getimagesize($compare), getimagesize(__DIR__ . '/tmp/test.jpg'));
    }

    public function testLandscapeFit()
    {
        $sample = __DIR__ . '/images/samples/sample1.jpg';
        $creator = new ThumbnailCreator();
        $creator->setSource(new File($sample));
        $result = $creator->fit(array('width' => 500, 'height' => 200));
        $compare = __DIR__ . '/images/compare/fit_sample1_500_200.jpg';
        file_put_contents(__DIR__ . '/tmp/test.jpg', $result);
        $this->assertEquals(getimagesize($compare), getimagesize(__DIR__ . '/tmp/test.jpg'));
    }

    public function testLandscapeBorder()
    {
        $sample = __DIR__ . '/images/samples/sample1.jpg';
        $creator = new ThumbnailCreator();
        $creator->setSource(new File($sample));
        $result = $creator->border(array('width' => 500, 'height' => 200));
        $compare = __DIR__ . '/images/compare/border_sample1_500_200.jpg';
        file_put_contents(__DIR__ . '/tmp/test.jpg', $result);
        $this->assertEquals(getimagesize($compare), getimagesize(__DIR__ . '/tmp/test.jpg'));
    }

    public function testPortraitCrop()
    {
        $sample = __DIR__ . '/images/samples/sample2.jpg';
        $creator = new ThumbnailCreator();
        $creator->setSource(new File($sample));
        $result = $creator->crop(array('width' => 200, 'height' => 500));
        $compare = __DIR__ . '/images/compare/crop_sample2_200_500.jpg';
        file_put_contents(__DIR__ . '/tmp/test.jpg', $result);
        $this->assertEquals(getimagesize($compare), getimagesize(__DIR__ . '/tmp/test.jpg'));
    }

    public function testPortraitResize()
    {
        $sample = __DIR__ . '/images/samples/sample2.jpg';
        $creator = new ThumbnailCreator();
        $creator->setSource(new File($sample));
        $result = $creator->resize(array('width' => 200, 'height' => 500));
        $compare = __DIR__ . '/images/compare/resize_sample2_200_500.jpg';
        file_put_contents(__DIR__ . '/tmp/test.jpg', $result);
        $this->assertEquals(getimagesize($compare), getimagesize(__DIR__ . '/tmp/test.jpg'));
    }

    public function testPortraitFit()
    {
        $sample = __DIR__ . '/images/samples/sample2.jpg';
        $creator = new ThumbnailCreator();
        $creator->setSource(new File($sample));
        $result = $creator->fit(array('width' => 200, 'height' => 500));
        $compare = __DIR__ . '/images/compare/fit_sample2_200_500.jpg';
        file_put_contents(__DIR__ . '/tmp/test.jpg', $result);
        $this->assertEquals(getimagesize($compare), getimagesize(__DIR__ . '/tmp/test.jpg'));
    }

    public function testPortraitBorder()
    {
        $sample = __DIR__ . '/images/samples/sample2.jpg';
        $creator = new ThumbnailCreator();
        $creator->setSource(new File($sample));
        $result = $creator->border(array('width' => 200, 'height' => 500));
        $compare = __DIR__ . '/images/compare/border_sample2_200_500.jpg';
        file_put_contents(__DIR__ . '/tmp/test.jpg', $result);
        $this->assertEquals(getimagesize($compare), getimagesize(__DIR__ . '/tmp/test.jpg'));
    }

    public function tearDown()
    {
        $tmp = __DIR__ . '/tmp/test.jpg';
        if (is_readable($tmp)) {
            unlink($tmp);
        }
    }
}
