<?php

namespace Tests\AppBundle\Cache;

use AppBundle\Cache\OpcacheClearer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;

class OpcacheClearerTest extends TestCase
{
    public function testClear()
    {
        $opcacheClearer = new OpcacheClearer();

        $this->assertTrue(isset(opcache_get_status()['scripts']));
        $opcacheClearer->clear();
        $this->assertFalse(isset(opcache_get_status()['scripts']));

        $this->assertInstanceOf(CacheClearerInterface::class, $opcacheClearer);
    }
}
