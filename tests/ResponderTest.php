<?php
namespace Bolt\Thumbs\Tests;

use Bolt\Filesystem;
use Bolt\Filesystem\Image;
use Bolt\Thumbs\CreatorInterface;
use Bolt\Thumbs\FinderInterface;
use Bolt\Thumbs\Responder;
use Bolt\Thumbs\Transaction;
use Doctrine\Common\Cache\ArrayCache;

class ResponderTest extends \PHPUnit_Framework_TestCase
{
    /** @var Filesystem\Manager */
    protected $fs;
    /** @var CreatorTester */
    protected $creator;
    /** @var FinderTester */
    protected $finder;
    /** @var Image */
    protected $errorImage;
    /** @var Image */
    protected $defaultImage;
    /** @var ArrayCache */
    protected $cache;

    public function setup()
    {
        $images = new Filesystem\Filesystem(new Filesystem\Local(__DIR__ . '/images'));
        $tmp = new Filesystem\Filesystem(new Filesystem\Local(__DIR__ . '/tmp'));
        $web = new Filesystem\Filesystem(new Filesystem\Local(__DIR__ . '/tmp/web'));
        $this->fs = new Filesystem\Manager(
            [
                'web'    => $web,
                'tmp'    => $tmp,
                'images' => $images,
            ]
        );
        $this->errorImage = $this->fs->getImage('images://samples/sample1.jpg');
        $this->defaultImage = $this->fs->getImage('images://samples/sample2.jpg');

        $this->creator = new CreatorTester();
        $this->finder = new FinderTester($this->defaultImage);
        $this->cache = new ArrayCache();
    }

    public function tearDown()
    {
        $this->fs->deleteDir('tmp://web');
    }

    public function testErrorImage()
    {
        $this->createResponder()
            ->respond(new Transaction('herp/derp.png'))
        ;

        $this->assertSame($this->creator->transaction->getErrorImage(), $this->errorImage);
    }

    public function testSrcImage()
    {
        $this->createResponder()
            ->respond(new Transaction('herp/derp.png'))
        ;

        $this->assertSame($this->creator->transaction->getSrcImage(), $this->defaultImage);
    }

    public function testCaching()
    {
        $this->creator = $this->getMock('\Bolt\Thumbs\CreatorInterface');
        $this->creator->expects($this->once())
            ->method('create')
            ->willReturnCallback(
                function (Transaction $transaction) {
                    return $transaction->getHash();
                }
            )
        ;
        $responder = $this->createResponder();

        // First call should populate cache
        $transaction = new Transaction('herp/derp.png');
        $thumbnail = $responder->respond($transaction);
        $this->assertSame($transaction->getHash(), $thumbnail->getThumbnail());

        // Second call should pull value from cache
        $thumbnail = $responder->respond($transaction);
        $this->assertSame($transaction->getHash(), $thumbnail->getThumbnail());
    }

    public function testStatic()
    {
        $responder = $this->createResponder(true);

        $requestPath = 'thumbs/100x100/herp/derp.png';
        $transaction = new Transaction('herp/derp.png', null, null, $requestPath);
        $thumbnail = $responder->respond($transaction);

        $this->assertSame($thumbnail->getThumbnail(), $this->fs->read('web://' . $requestPath));
    }

    protected function createResponder($saveFiles = false)
    {
        $responder = new Responder(
            $this->creator,
            $this->finder,
            $this->errorImage,
            $saveFiles ? $this->fs->getFilesystem('web') : null,
            $this->cache
        );

        return $responder;
    }
}

class CreatorTester implements CreatorInterface
{
    /** @var Transaction */
    public $transaction;

    public function create(Transaction $transaction)
    {
        $this->transaction = $transaction;
        return $transaction->getHash();
    }
}

class FinderTester implements FinderInterface
{
    /** @var Image */
    protected $image;

    public function __construct(Image $image)
    {
        $this->image = $image;
    }

    public function find($path)
    {
        return $this->image;
    }
}
