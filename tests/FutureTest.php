<?php

use EorBah545\Asyncio\Tests\AsyncTestCase;
use EorBah545\Asyncio\Future;
use function EorBah545\Asyncio\await;

class FutureTest extends AsyncTestCase
{
    public function testSetResultThenGet(): void
    {
        $future = new Future();
        $future->set_result(42);
        $this->assertEquals(42, $future->result());
    }

    public function testResultThrowsIfNotSet(): void
    {
        $future = new Future();
        $this->expectException(\RuntimeException::class);
        $future->result();
    }

    public function testThenCallback(): void
    {
        $future = new Future();
        $callbackFired = false;
        $future->then(function ($val) use (&$callbackFired) {
            $callbackFired = true;
            $this->assertEquals(42, $val);
        });
        $future->set_result(42);
        $this->assertTrue($callbackFired);
    }

    public function testAwait(): void
    {
        $future = new Future();
        $result = $this->await(function () use ($future) {
            $setter = new \Fiber(function () use ($future) {
                $future->set_result(99);
            });
            \EorBah545\Asyncio\Scheduler::getInstance()->spawn($setter);
            return await($future->await());
        });
        $this->assertEquals(99, $result);
    }
}