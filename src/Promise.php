<?php

namespace EorBah545\Asyncio;

use Fiber;

class Promise
{
    private string $state = 'pending';
    private mixed $value = null;
    private mixed $reason = null;
    private array $onFulfilled = [];
    private array $onRejected = [];

    /**
     * Crée une promesse immédiatement résolue.
     */
    public static function resolve(mixed $value): self
    {
        $promise = new self();
        $promise->state = 'fulfilled';
        $promise->value = $value;
        return $promise;
    }

    /**
     * Crée une promesse immédiatement rejetée.
     */
    public static function reject(mixed $reason): self
    {
        $promise = new self();
        $promise->state = 'rejected';
        $promise->reason = $reason;
        return $promise;
    }

    /**
     * Construit une promesse avec un exécuteur (callable).
     */
    public function __construct(?callable $executor = null)
    {
        if ($executor !== null) {
            // Ces fermetures capturent $this et modifient directement l'état
            $resolve = function (mixed $value): void {
                if ($this->state !== 'pending') return;
                $this->state = 'fulfilled';
                $this->value = $value;
                $this->triggerCallbacks();
            };
            $reject = function (mixed $reason): void {
                if ($this->state !== 'pending') return;
                $this->state = 'rejected';
                $this->reason = $reason;
                $this->triggerCallbacks();
            };
            try {
                $executor($resolve, $reject);
            } catch (\Throwable $e) {
                $reject($e);
            }
        }
    }

    private function triggerCallbacks(): void
    {
        if ($this->state === 'fulfilled') {
            foreach ($this->onFulfilled as $cb) {
                $cb($this->value);
            }
        } elseif ($this->state === 'rejected') {
            foreach ($this->onRejected as $cb) {
                $cb($this->reason);
            }
        }
        $this->onFulfilled = [];
        $this->onRejected = [];
    }

    public function then(?callable $onFulfilled = null, ?callable $onRejected = null): self
    {
        return new self(function ($resolve, $reject) use ($onFulfilled, $onRejected) {
            $handler = function () use ($resolve, $reject, $onFulfilled, $onRejected) {
                try {
                    if ($this->state === 'fulfilled') {
                        $val = $onFulfilled ? $onFulfilled($this->value) : $this->value;
                        $resolve($val);
                    } else {
                        if ($onRejected) {
                            $val = $onRejected($this->reason);
                            $resolve($val);
                        } else {
                            $reject($this->reason);
                        }
                    }
                } catch (\Throwable $e) {
                    $reject($e);
                }
            };

            if ($this->state === 'pending') {
                $this->onFulfilled[] = $handler;
                $this->onRejected[] = $handler;
            } else {
                $handler();
            }
        });
    }

    public function catch(callable $onRejected): self
    {
        return $this->then(null, $onRejected);
    }

    /**
     * Suspends la Fiber courante jusqu'à ce que la promesse soit résolue/rejetée.
     */
    public function await(): mixed
    {
        if ($this->state === 'fulfilled') {
            return $this->value;
        }
        if ($this->state === 'rejected') {
            throw $this->reason instanceof \Throwable ? $this->reason : new \RuntimeException((string)$this->reason);
        }

        $fiber = Fiber::getCurrent();
        if ($fiber === null) {
            throw new \RuntimeException("await ne peut être appelé qu'à l'intérieur d'une coroutine (Fiber)");
        }

        $this->then(
            function ($value) use ($fiber) {
                Scheduler::getInstance()->enqueue($fiber, $value);
            },
            function ($reason) use ($fiber) {
                Scheduler::getInstance()->enqueue($fiber, null, $reason);
            }
        );

        return Fiber::suspend();
    }
    
    /**
 * Attend que toutes les promesses soient résolues.
 * Rejette dès qu'une promesse échoue.
 */
public static function all(array $promises): Promise
{
    return new Promise(function ($resolve, $reject) use ($promises) {
        $results = [];
        $remaining = count($promises);
        if ($remaining === 0) {
            $resolve([]);
            return;
        }
        foreach ($promises as $index => $promise) {
            $promise->then(
                function ($value) use (&$results, &$remaining, $resolve, $index) {
                    $results[$index] = $value;
                    $remaining--;
                    if ($remaining === 0) {
                        $resolve($results);
                    }
                },
                function ($reason) use ($reject) {
                    $reject($reason);
                }
            );
        }
    });
}

/**
 * Attend que toutes les promesses soient terminées (succès ou échec).
 * Résout avec un tableau de résultats [{status: 'fulfilled', value: ...}, ...]
 */
public static function allSettled(array $promises): Promise
{
    return new Promise(function ($resolve) use ($promises) {
        $results = [];
        $remaining = count($promises);
        if ($remaining === 0) {
            $resolve([]);
            return;
        }
        foreach ($promises as $index => $promise) {
            $promise->then(
                function ($value) use (&$results, &$remaining, $resolve, $index) {
                    $results[$index] = ['status' => 'fulfilled', 'value' => $value];
                    $remaining--;
                    if ($remaining === 0) $resolve($results);
                },
                function ($reason) use (&$results, &$remaining, $resolve, $index) {
                    $results[$index] = ['status' => 'rejected', 'reason' => $reason instanceof \Throwable ? $reason->getMessage() : (string)$reason];
                    $remaining--;
                    if ($remaining === 0) $resolve($results);
                }
            );
        }
    });
}

/**
 * Première promesse terminée (succès ou échec) l'emporte.
 */
public static function race(array $promises): Promise
{
    return new Promise(function ($resolve, $reject) use ($promises) {
        foreach ($promises as $promise) {
            $promise->then($resolve, $reject);
        }
    });
}
}