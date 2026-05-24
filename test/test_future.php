<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use EorBah545\Asyncio\Future;
use function EorBah545\Asyncio\await;

$future = new Future();

// Dans une coroutine, on peut attendre le résultat
Scheduler::getInstance()->run(function () use ($future) {
    echo "Attente du résultat...\n";
    $val = await($future->await());   // suspend jusqu'à set_result
    echo "Résultat reçu : $val\n";
});

// Ailleurs (ou dans une autre coroutine), on fournit le résultat
$future->set_result(42);