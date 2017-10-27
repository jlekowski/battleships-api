<?php

namespace Tests\AppBundle\Cache;

use AppBundle\Cache\VarnishClearer;
use FOS\HttpCache\CacheInvalidator;
use PHPUnit\Framework\TestCase;

class VarnishClearerTest extends TestCase
{
    public function testClear()
    {
        $cacheInvalidator = $this->prophesize(CacheInvalidator::class);
        $varnishClearer = new VarnishClearer($cacheInvalidator->reveal());

        $cacheInvalidator->invalidateRegex('/')->shouldBeCalled();

        $varnishClearer->clear();
    }

    public function testClearWithoutCacheInvalidator()
    {
        $varnishClearer = new VarnishClearer();
        $varnishClearer->clear(); // does nothing, but throws no error
    }
}
