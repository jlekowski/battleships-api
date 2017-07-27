<?php

namespace Tests\AppBundle\Cache;

use AppBundle\Cache\ApcClearer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;

class ApcClearerTest extends TestCase
{
    public function testClear()
    {
        $apcClear = new ApcClearer();

        $key = 'test';
        $value = 'testValue';
        apc_store($key, $value);

        $this->assertEquals($value, apc_fetch($key));
        $apcClear->clear();
        $this->assertFalse(apc_fetch($key));

        $this->assertInstanceOf(CacheClearerInterface::class, $apcClear);
    }
}
