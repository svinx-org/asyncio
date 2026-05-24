<?php

use EorBah545\Asyncio\Tests\AsyncTestCase;
use EorBah545\Asyncio\Scheduler;
use function EorBah545\Asyncio\await;

class SchedulerTest extends AsyncTestCase
{
    public function testRunSimpleCoroutine(): void
    {
        $result = $this->await(function () {
            return 42;
        });
        $this->assertEquals(42, $result);
    }

    public function testRunMultipleFibers(): void
    {
        $results = [];
        $this->await(function () use (&$results) {
            $f1 = new \Fiber(function () use (&$results) {
                $results[] = 1;
                await(\EorBah545\Asyncio\Asyncio::sleep(0.01));
                $results[] = 2;
            });
            $f2 = new \Fiber(function () use (&$results) {
                $results[] = 3;
            });
            Scheduler::getInstance()->spawn($f1);
            Scheduler::getInstance()->spawn($f2);
        });
        $this->assertEquals([1,3,2], $results);
    }
}