<?php
namespace Bolt\Thumbs\Tests;

use Bolt\Filesystem\Filesystem;
use Bolt\Filesystem\Image;
use Bolt\Filesystem\ImageInfo;
use Bolt\Filesystem\Local;
use Bolt\Thumbs\Dimensions;
use Bolt\Thumbs\ThumbnailCreator;
use Bolt\Thumbs\Transaction;

class ThumbnailCreatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var Filesystem */
    protected $fs;

    /** @var Image */
    protected $logoJpg;
    /** @var Image */
    protected $landscapeImage;
    /** @var Image */
    protected $portraitImage;

    public function setup()
    {
        $this->fs = new Filesystem(new Local(__DIR__ . '/images'));
        $this->logoJpg = $this->fs->getImage('generic-logo.jpg');
        $this->landscapeImage = $this->fs->getImage('samples/sample1.jpg');
        $this->portraitImage = $this->fs->getImage('samples/sample2.jpg');
    }

    /**
     * @testdox When target dimensions are (0, 0), thumbnail dimensions are set to image dimensions
     */
    public function testFallbacksForAutoscale()
    {
        $transaction = Transaction::create()
            ->setSrcImage($this->portraitImage) // 427x640
            ->setTarget(new Dimensions(0, 0))
        ;
        $this->assertTransactionDimensions(new Dimensions(427, 640), $transaction);
    }

    /**
     * @testdox When target width is 0, thumbnail width is autoscaled based on image ratio
     */
    public function testFallbacksForHorizontalAutoscale()
    {
        $transaction = Transaction::create()
            ->setSrcImage($this->portraitImage) // 427x640
            ->setTarget(new Dimensions(0, 320))
        ;
        $this->assertTransactionDimensions(new Dimensions(214, 320), $transaction);
    }

    /**
     * @testdox When target height is 0, thumbnail height is autoscaled based on image ratio
     */
    public function testFallbacksForVerticalAutoscale()
    {
        $transaction = Transaction::create()
            ->setSrcImage($this->landscapeImage) // 1000x667
            ->setTarget(new Dimensions(500, 0))
        ;
        $this->assertTransactionDimensions(new Dimensions(500, 334), $transaction);
    }

    /**
     * @testdox When upscaling is allowed, thumbnail is enlarged to target dimensions
     */
    public function testUpscalingAllowed()
    {
        $upscaled = new Dimensions(800, 600);
        $transaction = Transaction::create()
            ->setAllowUpscale(true)
            ->setSrcImage($this->logoJpg)
            ->setTarget($upscaled)
        ;
        $this->assertTransactionDimensions($upscaled, $transaction);
    }

    /**
     * @testdox When upscaling is not allowed, target dimensions are reduced to current image dimensions
     */
    public function testUpscalingNotAllowed()
    {
        $upscaled = new Dimensions(800, 600);
        $original = new Dimensions(624, 351);

        $transaction = Transaction::create()
            ->setAllowUpscale(false)
            ->setSrcImage($this->logoJpg)
            ->setTarget($upscaled)
        ;
        $this->assertTransactionDimensions($original, $transaction);
    }

    public function testLandscapeCrop()
    {
        $expected = new Dimensions(500, 200);
        $transaction = Transaction::create()
            ->setSrcImage($this->landscapeImage)
            ->setAction('crop')
            ->setTarget($expected)
        ;
        $this->assertTransactionDimensions($expected, $transaction);
    }

    public function testLandscapeResize()
    {
        $transaction = Transaction::create()
            ->setSrcImage($this->landscapeImage)
            ->setAction('resize')
            ->setTarget(new Dimensions(500, 200))
        ;
        $this->assertTransactionDimensions(new Dimensions(299, 200), $transaction);
    }

    public function testLandscapeFit()
    {
        $expected = new Dimensions(500, 200);
        $transaction = Transaction::create()
            ->setSrcImage($this->landscapeImage)
            ->setAction('fit')
            ->setTarget($expected)
        ;
        $this->assertTransactionDimensions($expected, $transaction);
    }

    public function testLandscapeBorder()
    {
        $expected = new Dimensions(500, 200);
        $transaction = Transaction::create()
            ->setSrcImage($this->landscapeImage)
            ->setAction('border')
            ->setTarget($expected)
        ;
        $this->assertTransactionDimensions($expected, $transaction);
    }

    public function testPortraitCrop()
    {
        $expected = new Dimensions(200, 500);
        $transaction = Transaction::create()
            ->setSrcImage($this->portraitImage)
            ->setAction('crop')
            ->setTarget($expected)
        ;
        $this->assertTransactionDimensions($expected, $transaction);
    }

    public function testPortraitResize()
    {
        $transaction = Transaction::create()
            ->setSrcImage($this->portraitImage)
            ->setAction('resize')
            ->setTarget(new Dimensions(200, 500))
        ;
        $this->assertTransactionDimensions(new Dimensions(200, 299), $transaction);
    }

    public function testPortraitFit()
    {
        $expected = new Dimensions(200, 500);
        $transaction = Transaction::create()
            ->setSrcImage($this->portraitImage)
            ->setAction('fit')
            ->setTarget($expected)
        ;
        $this->assertTransactionDimensions($expected, $transaction);
    }

    public function testPortraitBorder()
    {
        $expected = new Dimensions(200, 500);
        $transaction = Transaction::create()
            ->setSrcImage($this->portraitImage)
            ->setAction('border')
            ->setTarget($expected)
        ;
        $this->assertTransactionDimensions($expected, $transaction);
    }

    protected function runTransaction(Transaction $transaction)
    {
        $creator = new ThumbnailCreator();
        return $creator->create($transaction);
    }

    protected function assertTransactionDimensions(Dimensions $expected, Transaction $transaction)
    {
        $result = $this->runTransaction($transaction);

        $actual = ImageInfo::createFromString($result)->getDimensions();
        $this->assertDimensions($expected, $actual);
    }

    protected function assertDimensions(Dimensions $expected, Dimensions $actual)
    {
        $this->assertEquals($expected, $actual, "Expected dimension $expected does not equal actual $actual");
    }
}
