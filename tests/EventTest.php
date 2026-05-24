<?php

use EorBah545\Asyncio\Tests\AsyncTestCase;
use EorBah545\Asyncio\Event;
use function EorBah545\Asyncio\await;

class EventTest extends AsyncTestCase
{
    public function testSetBeforeWait(): void
    {
        $event = new Event();
        $event->set();
        $this->await(function () use ($event) {
            await($event->wait()); // résout immédiatement
        });
        $this->assertTrue(true);
    }

    public function testWaitBeforeSet(): void
    {
        $event = new Event();
        $resolved = false;
        $this->await(function () use ($event, &$resolved) {
            $waiter = new \Fiber(function () use ($event, &$resolved) {
                await($event->wait());
                $resolved = true;
            });
            $setter = new \Fiber(function () use ($event) {
                $event->set();
            });
            \EorBah545\Asyncio\Scheduler::getInstance()->spawn($waiter);
            \EorBah545\Asyncio\Scheduler::getInstance()->spawn($setter);
        });
        $this->assertTrue($resolved);
    }

    public function testClearResets(): void
    {
        $event = new Event();
        $event->set();
        $event->clear();
        $resolved = false;
        $this->await(function () use ($event, &$resolved) {
            $waiter = new \Fiber(function () use ($event, &$resolved) {
                await($event->wait());
                $resolved = true;
            });
            \EorBah545\Asyncio\Scheduler::getInstance()->spawn($waiter);
            // On ne set pas, le waiter doit rester bloqué
        });
        $this->assertFalse($resolved, "Le waiter ne doit pas être résolu après clear");
    }
}