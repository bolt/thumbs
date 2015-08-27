<?php

namespace Bolt\Thumbs;

interface ThumbnailCreatorInterface
{
    /**
     * Creates a thumbnail for the given transaction.
     *
     * @param Transaction $transaction
     *
     * @return string thumbnail data
     */
    public function create(Transaction $transaction);
}
