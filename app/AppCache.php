<?php

use Symfony\Bundle\FrameworkBundle\HttpCache\HttpCache;

class AppCache extends HttpCache
{
    /**
     * @inheritdoc
     */
    protected function getOptions()
    {
        // @todo read more http://symfony.com/doc/current/book/http_cache.html
        return [
            'debug' => false,
            'private_headers' => [],
            'allow_reload' => true
        ];
    }
}
