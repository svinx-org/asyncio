<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use EorBah545\Examples\HttpServer;
use EorBah545\Asyncio\Asyncio;
use function EorBah545\Asyncio\await;

$server = new HttpServer("My App");

$server->get('/', function ($request, Asyncio $async) {
    await($async::sleep(1)); // simule un traitement asynchrone
    return ['message' => 'Hello World', 'path' => $request['path']];
});

$server->post('/data', function ($request, Asyncio $async) {
    await($async::sleep(0.5));
    $body = json_decode($request['body'], true);
    return ['received' => $body, 'method' => 'POST'];
});

// Route 404 implicite

$server->run(8000);