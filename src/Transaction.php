<?php

namespace Bolt\Thumbs;

use Bolt\Filesystem\Image;

/**
 * A storage entity for a thumbnail creation request.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class Transaction
{
    /** @var string */
    protected $filePath;
    /** @var Image */
    protected $srcImage;
    /** @var Image */
    protected $errorImage;

    /** @var string */
    protected $action;
    /** @var Dimensions */
    protected $target;

    /** @var string|null */
    protected $requestPath;

    /**
     * Transaction Constructor.
     *
     * @param Image|string $file
     * @param string       $action
     * @param Dimensions   $dimensions
     * @param string|null  $requestPath
     */
    public function __construct($file, $action = Action::CROP, Dimensions $dimensions = null, $requestPath = null)
    {
        if ($file instanceof Image) {
            $this->srcImage = $file;
        } else {
            $this->filePath = $file;
        }
        $this->action = $action ?: Action::CROP;
        $this->target = $dimensions ?: new Dimensions();
        $this->requestPath = $requestPath;
    }

    /**
     * Returns a hash string of this transaction.
     *
     * @return string
     */
    public function getHash()
    {
        $path = str_replace('/', '_', $this->getFilePath());
        return join('-', [$path, $this->action, $this->target->getWidth(), $this->target->getHeight()]);
    }

    /**
     * Returns the request path for this thumbnail, used for saving a static file.
     *
     * @return string
     */
    public function getRequestPath()
    {
        return $this->requestPath;
    }

    /**
     * Returns the filepath. Used for finding image in filesystem.
     *
     * @return string
     */
    public function getFilePath()
    {
        if ($this->srcImage !== null) {
            return $this->srcImage->getPath();
        }

        return $this->filePath;
    }

    /**
     * @return Image
     */
    public function getSrcImage()
    {
        return $this->srcImage;
    }

    /**
     * @param Image $srcImage
     *
     * @return Transaction
     */
    public function setSrcImage(Image $srcImage)
    {
        $this->srcImage = $srcImage;

        return $this;
    }

    /**
     * @return Image
     */
    public function getErrorImage()
    {
        return $this->errorImage;
    }

    /**
     * @param Image $errorImage
     *
     * @return Transaction
     */
    public function setErrorImage(Image $errorImage)
    {
        $this->errorImage = $errorImage;

        return $this;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param string $action
     *
     * @return Transaction
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @return Dimensions
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * @param Dimensions $target
     *
     * @return Transaction
     */
    public function setTarget(Dimensions $target)
    {
        $this->target = $target;

        return $this;
    }
}
