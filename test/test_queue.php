<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use EorBah545\Asyncio\Queue;
use EorBah545\Asyncio\Scheduler;
use EorBah545\Asyncio\Promise;
use function EorBah545\Asyncio\await;


$queue = new Queue(2);

$scheduler = Scheduler::getInstance();

$scheduler->run(function () use ($queue, $scheduler) {
    // Producteur
    $producer = new Fiber(function () use ($queue) {
        for ($i = 0; $i < 5; $i++) {
            echo "[P] Ajout $i\n";
            await($queue->put($i));
            echo "[P] $i ajouté\n";
        }
    });

    // Consommateur
    $consumer = new Fiber(function () use ($queue) {
        for ($i = 0; $i < 5; $i++) {
            echo "[C] Attente...\n";
            $val = await($queue->get());
            echo "[C] Reçu $val\n";
        }
    });

    // On les démarre
    $scheduler->spawn($producer);
    $scheduler->spawn($consumer);
});