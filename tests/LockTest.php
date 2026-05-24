<?php

use EorBah545\Asyncio\Tests\AsyncTestCase;
use EorBah545\Asyncio\Lock;
use function EorBah545\Asyncio\await;

class LockTest extends AsyncTestCase
{
    public function testAcquireRelease(): void
    {
        $lock = new Lock();
        $this->await(function () use ($lock) {
            await($lock->acquire());
            // section critique
            $lock->release();
            // doit pouvoir réacquérir immédiatement
            await($lock->acquire());
            $lock->release();
        });
        $this->assertTrue(true);
    }

    public function testExclusiveAccess(): void
    {
        $lock = new Lock();
        $results = [];
        $this->await(function () use ($lock, &$results) {
            $task1 = new \Fiber(function () use ($lock, &$results) {
                await($lock->acquire());
                $results[] = 'task1 start';
                await(\EorBah545\Asyncio\Asyncio::sleep(0.01)); // petit délai
                $results[] = 'task1 end';
                $lock->release();
            });
            $task2 = new \Fiber(function () use ($lock, &$results) {
                await($lock->acquire());
                $results[] = 'task2 start';
                $lock->release();
            });
            \EorBah545\Asyncio\Scheduler::getInstance()->spawn($task1);
            \EorBah545\Asyncio\Scheduler::getInstance()->spawn($task2);
        });
        $this->assertEquals(['task1 start', 'task1 end', 'task2 start'], $results);
    }
}