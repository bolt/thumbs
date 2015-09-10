<?php

namespace Bolt\Thumbs\Tests;

use Bolt\Filesystem;
use Bolt\Thumbs\Finder;

class FinderTest extends \PHPUnit_Framework_TestCase
{
    /** @var Filesystem\Manager */
    protected $fs;
    /** @var Finder */
    protected $finder;

    public function setup()
    {
        $samples = new Filesystem\Filesystem(new Filesystem\Local(__DIR__ . '/images/samples'));
        $subdir = new Filesystem\Filesystem(new Filesystem\Local(__DIR__ . '/images/subdir'));
        $images = new Filesystem\Filesystem(new Filesystem\Local(__DIR__ . '/images'));
        $filesystems = [
            'samples' => $samples,
            'subdir'  => $subdir,
            'images'  => $images,
        ];

        $this->fs = new Filesystem\Manager($filesystems);

        $default = $images->getImage('samples/sample1.jpg');
        $this->finder = new Finder($this->fs, array_keys($filesystems), $default);
    }

    public function testFind()
    {
        $image = $this->finder->find('generic-logo.png');

        $this->assertSame($this->fs->getFilesystem('images'), $image->getFilesystem());
        $this->assertSame('generic-logo.png', $image->getPath());
    }

    public function testImageNotFoundUsesDefault()
    {
        $image = $this->finder->find('herp/derp.png');

        $this->assertSame($this->fs->getFilesystem('images'), $image->getFilesystem());
        $this->assertSame('samples/sample1.jpg', $image->getPath());
    }
}
