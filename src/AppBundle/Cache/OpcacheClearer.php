<?php

namespace AppBundle\Cache;

use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;

class OpcacheClearer implements CacheClearerInterface
{
    /**
     * @inheritdoc
     */
    public function clear($cacheDir = '')
    {
        opcache_reset();
    }
}
