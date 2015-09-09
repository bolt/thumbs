<?php

namespace Bolt\Thumbs;

/**
 * A Thumbnail Response.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class Response extends \Symfony\Component\HttpFoundation\Response
{
    /** @var Thumbnail */
    protected $thumbnail;

    /**
     * Constructor.
     *
     * @param Thumbnail $thumbnail        The thumbnail
     * @param int       $status           The response status code
     * @param array     $headers          An array of response headers
     * @param bool      $public           Thumbnails are public by default
     * @param bool      $autoEtag         Whether the ETag header should be automatically set
     * @param bool      $autoLastModified Whether the Last-Modified header should be automatically set
     */
    public function __construct(
        Thumbnail $thumbnail,
        $status = 200,
        $headers = [],
        $public = true,
        $autoEtag = false,
        $autoLastModified = true
    ) {
        parent::__construct(null, $status, $headers);

        $this->setThumbnail($thumbnail, $autoEtag, $autoLastModified);

        if ($public) {
            $this->setPublic();
        }
    }

    /**
     * Factory method for chainability.
     *
     * @param Thumbnail $thumbnail        The thumbnail
     * @param int       $status           The response status code
     * @param array     $headers          An array of response headers
     * @param bool      $public           Thumbnails are public by default
     * @param bool      $autoEtag         Whether the ETag header should be automatically set
     * @param bool      $autoLastModified Whether the Last-Modified header should be automatically set
     *
     * @return Response
     */
    public static function create(
        $thumbnail = null,
        $status = 200,
        $headers = [],
        $public = true,
        $autoEtag = false,
        $autoLastModified = true
    ) {
        return new static($thumbnail, $status, $headers, $public, $autoEtag, $autoLastModified);
    }

    /**
     * @return Thumbnail
     */
    public function getThumbnail()
    {
        return $this->thumbnail;
    }

    /**
     * @param Thumbnail $thumbnail
     * @param bool      $autoEtag
     * @param bool      $autoLastModified
     */
    public function setThumbnail(Thumbnail $thumbnail, $autoEtag = false, $autoLastModified = true)
    {
        $this->thumbnail = $thumbnail;
        $this->setContent($thumbnail);

        if ($autoEtag) {
            $this->setAutoEtag();
        }

        if ($autoLastModified) {
            $this->setAutoLastModified();
        }

        $this->headers->set('Content-Type', $thumbnail->getImage()->getMimetype());
    }

    /**
     * Automatically sets the Last-Modified header according the file modification date.
     */
    public function setAutoLastModified()
    {
        $this->setLastModified($this->thumbnail->getImage()->getCarbon());

        return $this;
    }

    /**
     * Automatically sets the ETag header according to the checksum of the file.
     */
    public function setAutoEtag()
    {
        $this->setEtag(sha1($this->thumbnail));

        return $this;
    }
}
