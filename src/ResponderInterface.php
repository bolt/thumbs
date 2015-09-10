<?php

namespace Bolt\Thumbs;

/**
 * @author Carson Full <carsonfull@gmail.com>
 */
interface ResponderInterface
{
    /**
     * Process the transaction and return a thumbnail.
     *
     * @param Transaction $transaction
     *
     * @return Thumbnail
     */
    public function respond(Transaction $transaction);
}
