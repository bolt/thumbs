<?php

namespace Bolt\Thumbs;

/**
 * Actions used when creating thumbnails.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
final class Action
{
    const CROP = 'crop';
    const RESIZE = 'resize';
    const BORDER = 'border';
    const FIT = 'fit';

    private function __construct() { }
}
