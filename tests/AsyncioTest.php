<?php

use EorBah545\Asyncio\Tests\AsyncTestCase;
use EorBah545\Asyncio\Asyncio;
use function EorBah545\Asyncio\await;

class AsyncioTest extends AsyncTestCase
{
    public function testSleep(): void
    {
        $start = microtime(true);
        $this->await(function () {
            await(Asyncio::sleep(0.5));
        });
        $duration = microtime(true) - $start;
        $this->assertGreaterThan(0.45, $duration);
        $this->assertLessThan(0.6, $duration);
    }

    public function testGather(): void
    {
        $results = $this->await(function () {
            return await(Asyncio::gather([
                Asyncio::createTask(fn() => 1),
                Asyncio::createTask(fn() => 2),
            ]));
        });
        $this->assertEquals([1,2], $results);
    }

    public function testAsyncFunction(): void
    {
        $asyncFunc = Asyncio::async(function ($x, $y) {
            await(Asyncio::sleep(0.01));
            return $x + $y;
        });
        $result = $this->await(function () use ($asyncFunc) {
            return await($asyncFunc(3, 4));
        });
        $this->assertEquals(7, $result);
    }

    public function testTimeoutSucceeds(): void
    {
        $result = $this->await(function () {
            return await(Asyncio::timeout(function () {
                return Asyncio::sleep(0.1)->then(fn() => 'done');
            }, 1));
        });
        $this->assertEquals('done', $result);
    }

    public function testTimeoutFails(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('TimeoutError');
        $this->await(function () {
            await(Asyncio::timeout(function () {
                return Asyncio::sleep(1);
            }, 0.1));
        });
    }
}