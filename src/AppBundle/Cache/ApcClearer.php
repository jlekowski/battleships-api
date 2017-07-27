<?php

namespace AppBundle\Cache;

use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;

class ApcClearer implements CacheClearerInterface
{
    /**
     * @inheritdoc
     */
    public function clear($cacheDir = '')
    {
        apc_clear_cache();
        apcu_clear_cache();
    }
}
