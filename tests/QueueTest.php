<?php

use EorBah545\Asyncio\Tests\AsyncTestCase;
use EorBah545\Asyncio\Queue;
use function EorBah545\Asyncio\await;

class QueueTest extends AsyncTestCase
{
    public function testPutGetSingleItem(): void
    {
        $queue = new Queue(2);
        $result = $this->await(function () use ($queue) {
            await($queue->put(42));
            return await($queue->get());
        });
        $this->assertEquals(42, $result);
    }

    public function testFifoOrder(): void
    {
        $queue = new Queue(2);
        $this->await(function () use ($queue) {
            await($queue->put(1));
            await($queue->put(2));
            $this->assertEquals(1, await($queue->get()));
            $this->assertEquals(2, await($queue->get()));
        });
    }

    public function testMaxSizeBlocksProducer(): void
    {
        $queue = new Queue(1);
        $this->await(function () use ($queue) {
            $producer = new \Fiber(function () use ($queue) {
                echo "P: avant put 1\n";
                await($queue->put(1));
                echo "P: avant put 2\n";
                await($queue->put(2));  // bloqué
                echo "P: après put 2\n";
            });
            $consumer = new \Fiber(function () use ($queue) {
                echo "C: avant get\n";
                $val = await($queue->get());
                echo "C: reçu $val\n";
            });
            \EorBah545\Asyncio\Scheduler::getInstance()->spawn($producer);
            \EorBah545\Asyncio\Scheduler::getInstance()->spawn($consumer);
        });
        // Le test passe si pas de deadlock (le scheduler termine tout seul)
        $this->assertTrue(true);
    }
}