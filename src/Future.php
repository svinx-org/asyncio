<?php

namespace EorBah545\Asyncio;

class Future
{
    private bool $isDone = false;
    private mixed $result = null;
    private array $callbacks = [];
    private ?Promise $promise = null;
    private ?\Closure $resolvePromise = null;

    /**
     * Définit le résultat de la future. Tous les callbacks enregistrés sont exécutés,
     * et la promesse interne (si créée) est résolue.
     */
    public function set_result(mixed $result): void
    {
        if ($this->isDone) {
            return;
        }
        $this->isDone = true;
        $this->result = $result;

        foreach ($this->callbacks as $callback) {
            $callback($result);
        }
        $this->callbacks = [];

        if ($this->resolvePromise !== null) {
            ($this->resolvePromise)($result);
        }
    }

    /**
     * Retourne le résultat si la future est complète, lève une exception sinon.
     * (Méthode synchrone, fidèle à la version JavaScript)
     */
    public function result(): mixed
    {
        if (!$this->isDone) {
            throw new \RuntimeException("Future is not complete");
        }
        return $this->result;
    }

    /**
     * Enregistre un callback qui sera exécuté dès que le résultat est disponible.
     * Si la future est déjà complète, le callback est appelé immédiatement.
     */
    public function then(callable $callback): void
    {
        if ($this->isDone) {
            $callback($this->result);
        } else {
            $this->callbacks[] = $callback;
        }
    }

    /**
     * Retourne une promesse qui sera résolue avec le résultat lorsque set_result est appelé.
     * Peut être utilisé directement avec await().
     */
    public function getPromise(): Promise
    {
        if ($this->promise === null) {
            $this->promise = new Promise(function ($resolve) {
                $this->resolvePromise = $resolve;
                if ($this->isDone) {
                    $resolve($this->result);
                }
            });
        }
        return $this->promise;
    }

    /**
     * Version asynchrone de result() : suspend la coroutine courante jusqu'à ce que le résultat soit prêt.
     * À utiliser avec await($future->await()).
     */
    public function await(): mixed
    {
        return $this->getPromise()->await();
    }
}