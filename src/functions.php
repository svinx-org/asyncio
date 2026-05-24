<?php

declare(strict_types=1);

namespace EorBah545\Asyncio;

/**
 * Suspend la coroutine courante jusqu'à ce que la promesse soit résolue.
 * 
 * Usage dans une coroutine :
 *   $valeur = await($unePromesse);
 */
function await(Promise $promise): mixed
{
    return $promise->await();
}