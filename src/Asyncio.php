<?php

namespace EorBah545\Asyncio;

class Asyncio
{
    // ---------- Utilitaires asynchrones ----------

    /**
     * Retourne une promesse résolue après $seconds secondes.
     */
    public static function sleep(float $seconds): Promise
    {
        return new Promise(function ($resolve) use ($seconds) {
            Scheduler::getInstance()->addTimer($seconds, function () use ($resolve) {
                $resolve(null);
            });
        });
    }

    /**
     * Équivalent de Promise.all()
     */
    public static function gather(array $tasks): Promise
    {
        return Promise::all($tasks);
    }

    /**
     * Équivalent de Promise.allSettled()
     */
    public static function wait(array $tasks): Promise
    {
        return Promise::allSettled($tasks);
    }

    /**
     * Lance une coroutine et retourne une promesse associée.
     * La coroutine est exécutée immédiatement dans une nouvelle Fiber.
     */
    public static function createTask(callable $coroutine): Promise
    {
        return new Promise(function ($resolve, $reject) use ($coroutine) {
            $fiber = new \Fiber(function () use ($coroutine, $resolve, $reject) {
                try {
                    $result = $coroutine();
                    if ($result instanceof Promise) {
                        // Si la coroutine retourne une promesse, on chaîne
                        $result->then($resolve, $reject);
                    } else {
                        $resolve($result);
                    }
                } catch (\Throwable $e) {
                    $reject($e);
                }
            });
            Scheduler::getInstance()->spawn($fiber);
        });
    }

    /**
     * Exécute une coroutine et bloque jusqu'à sa fin.
     * Retourne directement le résultat (ou lance une exception).
     */
    public static function run(callable $coroutine): mixed
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
        if ($exception) throw $exception;
        return $result;
    }

    /**
     * Timeout : exécute $coroutine, rejette après $seconds si pas terminée.
     */
    public static function timeout(callable $coroutine, float $seconds): Promise
    {
        $taskPromise = $coroutine();
        if (!($taskPromise instanceof Promise)) {
            $taskPromise = Promise::resolve($taskPromise);
        }

        $timeoutPromise = new Promise(function ($resolve, $reject) use ($seconds) {
            Scheduler::getInstance()->addTimer($seconds, function () use ($reject) {
                $reject(new \RuntimeException("TimeoutError"));
            });
        });

        return Promise::race([$taskPromise, $timeoutPromise]);
    }

    /**
     * Décorateur qui attrape les erreurs d'une fonction asynchrone et retourne null en cas d'échec.
     */
    public static function ErrorHandling(callable $asyncFunc): \Closure
    {
        return function (...$args) use ($asyncFunc) {
            try {
                $result = $asyncFunc(...$args);
                if ($result instanceof Promise) {
                    return $result->catch(function (\Throwable $e) use ($asyncFunc) {
                        error_log("Erreur dans la coroutine : " . $e->getMessage());
                        return null;
                    });
                }
                return $result;
            } catch (\Throwable $e) {
                error_log("Erreur dans la coroutine : " . $e->getMessage());
                return null;
            }
        };
    }

    /**
     * Transforme un callable en "fonction asynchrone" lançable.
     * Usage :
     *   $func = Asyncio::async(function($x, $y) { await(Asyncio::sleep(1)); return $x + $y; });
     *   $promise = $func(3, 4);   // retourne une Promise
     *   $resultat = await($promise);  // 7
     */
    public static function async(callable $callback): \Closure
    {
        return function (...$args) use ($callback): Promise {
            return self::createTask(function () use ($callback, $args) {
                return $callback(...$args);
            });
        };
    }
}