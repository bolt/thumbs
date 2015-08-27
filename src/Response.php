<?php

namespace Bolt\Thumbs;

/**
 * A Thumbnail Response.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class Response extends \Symfony\Component\HttpFoundation\Response
{
    /**
     * Constructor.
     *
     * @param string    $thumbnailData
     * @param ImageInfo $info
     * @param array     $headers
     */
    public function __construct($thumbnailData = '', $info = null, $headers = [])
    {
        parent::__construct($thumbnailData, static::HTTP_OK, $headers);
        if ($info) {
            $this->headers->set('Content-Type', $info->getMime());
        }
    }

    /**
     * Factory method for chainability.
     *
     * @param mixed     $thumbnailData
     * @param ImageInfo $info
     * @param array     $headers
     *
     * @return Response
     */
    public static function create($thumbnailData = '', $info = null, $headers = [])
    {
        return parent::create($thumbnailData, $info, $headers);
    }
}
