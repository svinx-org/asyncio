<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use EorBah545\Asyncio\Lock;
use function EorBah545\Asyncio\await;
$lock = new Lock();

// Dans une coroutine :
await($lock->acquire());
// section critique...
$lock->release();