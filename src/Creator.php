<?php
namespace Bolt\Thumbs;

use Exception;
use RuntimeException;

/**
 * Creates thumbnails.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Carson Full <carsonfull@gmail.com>
 */
class Creator implements CreatorInterface
{
    /** @var bool */
    protected $allowUpscale;
    /** @var Color */
    protected $background;

    /**
     * Creator constructor.
     *
     * @param bool  $allowUpscale
     * @param Color $background
     */
    public function __construct($allowUpscale = false, Color $background = null)
    {
        $this->allowUpscale = (bool) $allowUpscale;
        $this->background = $background ?: Color::white();
    }

    /**
     * {@inheritdoc}
     */
    public function create(Transaction $transaction)
    {
        $this->verifyInfo($transaction);

        $this->autoscale($transaction);

        $this->checkForUpscale($transaction);

        $data = $this->resize($transaction);

        return $data;
    }

    /**
     * Verifies that the image's info can be read correctly.
     * If the src image fails, it tries the error image as the fallback.
     *
     * @param Transaction $transaction
     *
     * @throws RuntimeException If both src and error images fail to be read.
     */
    protected function verifyInfo(Transaction $transaction)
    {
        try {
            $transaction->getSrcImage()->getInfo();
        } catch (Exception $e) {
            $transaction->setSrcImage($transaction->getErrorImage());
            try {
                $transaction->getSrcImage()->getInfo();
            } catch (Exception $e) {
                throw new RuntimeException(
                    'There was an error with the thumbnail image requested and additionally the fallback image could not be displayed.',
                    1
                );
            }
        }
    }

    /**
     * If target width and/or height are set to 0, they are set based on the image's height/width.
     *
     * @param Transaction $transaction
     */
    protected function autoscale(Transaction $transaction)
    {
        $info = $transaction->getSrcImage()->getInfo();

        $target = $transaction->getTarget();

        if ($target->getWidth() === 0 && $target->getHeight() === 0) {
            $target->setWidth($info->getWidth());
            $target->setHeight($info->getHeight());
        } elseif ($target->getWidth() === 0) {
            $target->setWidth(round($target->getHeight() * $info->getWidth() / $info->getHeight()));
        } elseif ($target->getHeight() === 0) {
            $target->setHeight(round($target->getWidth() * $info->getHeight() / $info->getWidth()));
        }
    }

    /**
     * Limits the target width/height to the image's height/width if upscale is not allowed.
     *
     * @param Transaction $transaction
     */
    protected function checkForUpscale(Transaction $transaction)
    {
        if ($this->allowUpscale) {
            return;
        }

        $info = $transaction->getSrcImage()->getInfo();
        $target = $transaction->getTarget();

        if ($target->getWidth() > $info->getWidth()) {
            $target->setWidth($info->getWidth());
        }
        if ($target->getHeight() > $info->getHeight()) {
            $target->setHeight($info->getHeight());
        }
    }

    /**
     * Do the actual resize/crop/fit/border logic and return the image data.
     *
     * @param Transaction $transaction
     *
     * @return string
     */
    protected function resize(Transaction $transaction)
    {
        $crop = $transaction->getAction() === 'crop';
        $fit = $transaction->getAction() === 'fit';
        $border = $transaction->getAction() === 'border';

        try {
            $img = ImageResource::createFromString($transaction->getSrcImage()->read());
        } catch (Exception $e) {
            return false;
        }

        $target = clone $transaction->getTarget();

        $original = $img->getDimensions();

        $point = new Point();

        if ($crop) {
            $xratio = $original->getWidth() / $target->getWidth();
            $yratio = $original->getHeight() / $target->getHeight();

            // calculate x or y coordinate and width or height of source
            if ($xratio > $yratio) {
                $point->setX(round(($original->getWidth() - ($original->getWidth() / $xratio * $yratio)) / 2));
                $original->setWidth(round($original->getWidth() / $xratio * $yratio));
            } elseif ($yratio > $xratio) {
                $point->setY(round(($original->getHeight() - ($original->getHeight() / $yratio * $xratio)) / 2));
                $original->setHeight(round($original->getHeight() / $yratio * $xratio));
            }
        } elseif (!$border && !$fit) {
            $ratio = min($target->getWidth() / $original->getWidth(), $target->getHeight() / $original->getHeight());
            $target->setWidth($original->getWidth() * $ratio);
            $target->setHeight($original->getHeight() * $ratio);
        }

        $new = ImageResource::createNew($target, $img->getType());

        if ($border) {
            $new->fill($this->background);

            $tmpheight = $original->getHeight() * ($target->getWidth() / $original->getWidth());
            if ($tmpheight > $target->getHeight()) {
                $target->setWidth($original->getWidth() * ($target->getHeight() / $original->getHeight()));
                $point->setX(round(($transaction->getTarget()->getWidth() - $target->getWidth()) / 2));
            } else {
                $target->setHeight($tmpheight);
                $point->setY(round(($transaction->getTarget()->getHeight() - $target->getHeight()) / 2));
            }
        }

        if (!$crop && !$border) {
            $img->resample(new Point(), new Point(), $target, $original, $new);
        } elseif ($border) {
            $img->resample($point, new Point(), $target, $original, $new);
        } else {
            $img->resample(new Point(), $point, $target, $original, $new);
        }

        return $img->toString();
    }
}
