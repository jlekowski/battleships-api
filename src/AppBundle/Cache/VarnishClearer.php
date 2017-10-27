<?php

namespace AppBundle\Cache;

use FOS\HttpCache\CacheInvalidator;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;

class VarnishClearer implements CacheClearerInterface
{
    /**
     * @var CacheInvalidator|null
     */
    protected $cacheInvalidator;

    /**
     * @param CacheInvalidator|null $cacheInvalidator
     */
    public function __construct(CacheInvalidator $cacheInvalidator = null)
    {
        $this->cacheInvalidator = $cacheInvalidator;
    }

    /**
     * @inheritdoc
     */
    public function clear($cacheDir = '')
    {
        // null if varnish not enabled
        if ($this->cacheInvalidator !== null) {
            $this->cacheInvalidator->invalidateRegex('/');
        }
    }
}
