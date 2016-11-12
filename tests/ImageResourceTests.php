<?php

namespace Bolt\Thumbs\Tests;

use Bolt\Filesystem\Adapter\Local;
use Bolt\Filesystem\Filesystem;
use Bolt\Filesystem\Handler\Image\Dimensions;
use Bolt\Thumbs\ImageResource;
use Bolt\Thumbs\Point;

class ImageResourceTests extends \PHPUnit_Framework_TestCase
{
    /** @var Filesystem */
    protected $fs;

    public function setup()
    {
        $this->fs = new Filesystem(new Local(__DIR__ . '/images'));
    }

    public function testExifOrientation()
    {
        $images = [
            '1-top-left',
            '2-top-right',
            '3-bottom-right',
            '4-bottom-left',
            '5-left-top',
            '6-right-top',
            '7-right-bottom',
            '8-left-bottom',
        ];
        $expected = new Dimensions(400, 200);

        foreach ($images as $name) {
            $image = $this->fs->getImage('exif-orientation/' . $name . '.jpg');
            $resource = ImageResource::createFromString($image->read());

            $this->assertDimensions($expected, $resource->getDimensions());

            $color = $resource->getColorAt(new Point());
            $this->assertTrue(
                $color->getRed() > 250 && $color->getGreen() < 10 && $color->getBlue() < 5,
                'Wrong orientation'
            );
        }
    }

    protected function assertDimensions(Dimensions $expected, Dimensions $actual)
    {
        $this->assertEquals($expected, $actual, "Expected dimension $expected does not equal actual $actual");
    }
}
