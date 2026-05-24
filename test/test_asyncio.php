<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use EorBah545\Asyncio\Asyncio;
use EorBah545\Asyncio\Promise;
use function EorBah545\Asyncio\await;

// ===== 1. sleep + async =====
$task = Asyncio::async(function (string $name) {
    echo "[$name] Début\n";
    await(Asyncio::sleep(1.5));
    echo "[$name] Fin après 1.5s\n";
    return "$name OK";
});

echo "Lancement tâches...\n";
$prom1 = $task("T1");
$prom2 = $task("T2");

// On peut enchaîner avec then/catch
$prom1->then(function($res) {
    echo "T1 result: $res\n";
})
->catch(function($e) { 
    echo "T1 erreur: {$e->getMessage()}\n";
});

$prom2->then(function($res) {
    echo "T2 result: $res\n";
});

// Bloque jusqu'à ce que toutes les fibres soient terminées
Asyncio::run(function () {
    // Ici on peut faire d'autres opérations asynchrones
    echo "Dans run : attente d'un sleep de 0.5s\n";
    await(Asyncio::sleep(0.5));
    echo "Fin run\n";
});