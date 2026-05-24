<?php

namespace EorBah545\Asyncio\Tests;

use PHPUnit\Framework\TestCase;
use EorBah545\Asyncio\Scheduler;

abstract class AsyncTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Scheduler::getInstance()->reset();
    }

    /**
     * Exécute un callable asynchrone dans le scheduler et retourne sa valeur.
     */
    protected function await(callable $coroutine): mixed
    {
        $result = null;
        $exception = null;
        Scheduler::getInstance()->run(function () use ($coroutine, &$result, &$exception) {
            try {
                $result = $coroutine();
            } catch (\Throwable $e) {
                $exception = $e;
            }
        });
        if ($exception) {
            throw $exception;
        }
        return $result;
    }
}