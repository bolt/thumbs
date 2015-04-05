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
        $compare = __DIR__ . '/images/timthumbs/crop_sample1_500_200.jpg';
        file_put_contents(__DIR__ . '/tmp/test.jpg', $result);
        $this->assertEquals(getimagesize($compare), getimagesize(__DIR__ . '/tmp/test.jpg'));
        $this->assertEquals(filesize($compare), filesize(__DIR__ . '/tmp/test.jpg'));
    }

    public function testLandscapeResize()
    {
        $sample = __DIR__ . '/images/samples/sample1.jpg';
        $creator = new ThumbnailCreator();
        $creator->setSource(new File($sample));
        $result = $creator->resize(array('width' => 500, 'height' => 200));
        $compare = __DIR__ . '/images/timthumbs/resize_sample1_500_200.jpg';
        file_put_contents(__DIR__ . '/tmp/test.jpg', $result);
        $this->assertEquals(getimagesize($compare), getimagesize(__DIR__ . '/tmp/test.jpg'));
        $this->assertEquals(filesize($compare), filesize(__DIR__ . '/tmp/test.jpg'));
    }

    public function testLandscapeFit()
    {
        $sample = __DIR__ . '/images/samples/sample1.jpg';
        $creator = new ThumbnailCreator();
        $creator->setSource(new File($sample));
        $result = $creator->fit(array('width' => 500, 'height' => 200));
        $compare = __DIR__ . '/images/timthumbs/fit_sample1_500_200.jpg';
        file_put_contents(__DIR__ . '/tmp/test.jpg', $result);
        $this->assertEquals(getimagesize($compare), getimagesize(__DIR__ . '/tmp/test.jpg'));
        $this->assertEquals(filesize($compare), filesize(__DIR__ . '/tmp/test.jpg'));
    }

    public function testLandscapeBorder()
    {
        $sample = __DIR__ . '/images/samples/sample1.jpg';
        $creator = new ThumbnailCreator();
        $creator->setSource(new File($sample));
        $result = $creator->border(array('width' => 500, 'height' => 200));
        $compare = __DIR__ . '/images/timthumbs/border_sample1_500_200.jpg';
        file_put_contents(__DIR__ . '/tmp/test.jpg', $result);
        $this->assertEquals(getimagesize($compare), getimagesize(__DIR__ . '/tmp/test.jpg'));
        $this->assertEquals(filesize($compare), filesize(__DIR__ . '/tmp/test.jpg'));
    }

    public function testPortraitCrop()
    {
        $sample = __DIR__ . '/images/samples/sample2.jpg';
        $creator = new ThumbnailCreator();
        $creator->setSource(new File($sample));
        $result = $creator->crop(array('width' => 200, 'height' => 500));
        $compare = __DIR__ . '/images/timthumbs/crop_sample2_200_500.jpg';
        file_put_contents(__DIR__ . '/tmp/test.jpg', $result);
        $this->assertEquals(getimagesize($compare), getimagesize(__DIR__ . '/tmp/test.jpg'));
        $this->assertEquals(filesize($compare), filesize(__DIR__ . '/tmp/test.jpg'));
    }

    public function testPortraitResize()
    {
        $sample = __DIR__ . '/images/samples/sample2.jpg';
        $creator = new ThumbnailCreator();
        $creator->setSource(new File($sample));
        $result = $creator->resize(array('width' => 200, 'height' => 500));
        $compare = __DIR__ . '/images/timthumbs/resize_sample2_200_500.jpg';
        file_put_contents(__DIR__ . '/tmp/test.jpg', $result);

        // Original compare image is with v80, v90 creates a 2 byte smaller image (perhaps only on windows?)
        $correction = 0;
        if (preg_match('%CREATOR: gd-jpeg v1\.0 \(using IJG JPEG v(\d+)\)%', $result, $pm) && $pm[1] == '90') {
            $correction = 2;
        }

        $this->assertEquals(getimagesize($compare), getimagesize(__DIR__ . '/tmp/test.jpg'));
        $this->assertEquals(filesize($compare), filesize(__DIR__ . '/tmp/test.jpg') + $correction);
    }

    public function testPortraitFit()
    {
        $sample = __DIR__ . '/images/samples/sample2.jpg';
        $creator = new ThumbnailCreator();
        $creator->setSource(new File($sample));
        $result = $creator->fit(array('width' => 200, 'height' => 500));
        $compare = __DIR__ . '/images/timthumbs/fit_sample2_200_500.jpg';
        file_put_contents(__DIR__ . '/tmp/test.jpg', $result);
        $this->assertEquals(getimagesize($compare), getimagesize(__DIR__ . '/tmp/test.jpg'));
        $this->assertEquals(filesize($compare), filesize(__DIR__ . '/tmp/test.jpg'));
    }

    public function testPortraitBorder()
    {
        $sample = __DIR__ . '/images/samples/sample2.jpg';
        $creator = new ThumbnailCreator();
        $creator->setSource(new File($sample));
        $result = $creator->border(array('width' => 200, 'height' => 500));
        $compare = __DIR__ . '/images/timthumbs/border_sample2_200_500.jpg';
        file_put_contents(__DIR__ . '/tmp/test.jpg', $result);
        $this->assertEquals(getimagesize($compare), getimagesize(__DIR__ . '/tmp/test.jpg'));
        $this->assertEquals(filesize($compare), filesize(__DIR__ . '/tmp/test.jpg'));
    }

    public function testExifOrientation()
    {
        $images = array(
            '1-top-left',
            '2-top-right',
            '3-bottom-right',
            '4-bottom-left',
            '5-left-top',
            '6-right-top',
            '7-right-bottom',
            '8-left-bottom',
        );
        $resize = array('width' => 200, 'height' => 100);

        foreach ($images as $name) {
            $path = __DIR__ . '/tmp/' . $name . '.jpg';
            // Create test image
            $creator = new ThumbnailCreator();
            $creator->setSource(new File(__DIR__ . '/images/exif-orientation/' . $name . '.jpg'));
            $result = $creator->resize($resize);
            file_put_contents($path, $result);
            // Read test image
            $img = imagecreatefromjpeg($path);
            $width = imagesx($img);
            $height = imagesy($img);
            $rgb = imagecolorsforindex($img, imagecolorat($img, 0, 0));
            // Assert image size and red color (fuzzy) in the upper left corner
            $this->assertEquals($resize['width'], $width, 'Wrong width!');
            $this->assertEquals($resize['height'], $height, 'Wrong height!');
            $this->assertTrue($rgb['red'] > 250 && $rgb['green'] < 5 && $rgb['blue'] < 5, 'Wrong orientation!');
        }
    }

    public function tearDown()
    {
        $tmp = __DIR__ . '/tmp/test.jpg';
        if (is_readable($tmp)) {
            unlink($tmp);
        }
    }
}
