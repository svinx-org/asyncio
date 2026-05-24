<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use EorBah545\Asyncio\Event;
use function EorBah545\Asyncio\await;

$event = new Event();

// Dans une fibre
echo "En attente de l'événement...\n";
await($event->wait());
echo "Événement reçu !\n";

// Ailleurs, une autre fibre peut faire
$event->set();   // réveille toutes les fibres en attente sur wait()
