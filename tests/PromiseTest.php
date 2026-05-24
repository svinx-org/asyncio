<?php

use EorBah545\Asyncio\Tests\AsyncTestCase;
use EorBah545\Asyncio\Promise;
use function EorBah545\Asyncio\await;

class PromiseTest extends AsyncTestCase
{
    public function testResolveThen(): void
    {
        $promise = Promise::resolve(10);
        $val = null;
        $promise->then(function ($v) use (&$val) { $val = $v; });
        $this->assertEquals(10, $val);
    }

    public function testRejectCatch(): void
    {
        $promise = Promise::reject(new \Exception('fail'));
        $caught = null;
        $promise->catch(function ($e) use (&$caught) { $caught = $e->getMessage(); });
        $this->assertEquals('fail', $caught);
    }

    public function testAsyncResolve(): void
    {
        $result = $this->await(function () {
            $p = new Promise(function ($resolve) {
                $resolve(42);
            });
            return await($p);
        });
        $this->assertEquals(42, $result);
    }

    public function testAll(): void
    {
        $result = $this->await(function () {
            return await(Promise::all([
                Promise::resolve(1),
                Promise::resolve(2),
                new Promise(function ($resolve) { $resolve(3); })
            ]));
        });
        $this->assertEquals([1,2,3], $result);
    }

    public function testRace(): void
    {
        $result = $this->await(function () {
            $fast = Promise::resolve('fast');
            $slow = new Promise(function ($resolve) {
                \EorBah545\Asyncio\Scheduler::getInstance()->addTimer(0.1, fn() => $resolve('slow'));
            });
            return await(Promise::race([$slow, $fast]));
        });
        $this->assertEquals('fast', $result);
    }
}