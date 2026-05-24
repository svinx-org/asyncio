<?php

use EorBah545\Asyncio\Tests\AsyncTestCase;
use EorBah545\Asyncio\Event;
use EorBah545\Asyncio\Promise;
use EorBah545\Asyncio\Asyncio;
use function EorBah545\Asyncio\await;

class EventTest extends AsyncTestCase
{
    public function testSetBeforeWait(): void
    {
        $event = new Event();
        $event->set();
        $this->await(function () use ($event) {
            await($event->wait());
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
        $event->clear();   // remet à zéro
        $resolved = false;

        $this->await(function () use ($event, &$resolved) {
            // On lance un waiter qui ne doit jamais être réveillé par l’event
            $waiter = new \Fiber(function () use ($event, &$resolved) {
                // On fait une course entre l’événement et un timeout
                $result = await(Promise::race([
                    $event->wait()->then(fn() => true),
                    Asyncio::sleep(0.2)->then(fn() => false),
                ]));
                $resolved = $result;
            });
            \EorBah545\Asyncio\Scheduler::getInstance()->spawn($waiter);
            // On laisse le temps au waiter de se suspendre et d’attendre le timeout
            await(Asyncio::sleep(0.3));
        });

        $this->assertFalse($resolved, "Le waiter doit expirer sans être réveillé après clear");
    }
}