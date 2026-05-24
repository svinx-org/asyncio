<?php

namespace EorBah545\Asyncio;

class Queue
{
    private array $queue = [];
    private array $resolvers = [];
    private int $maxsize;

    public function __construct(int $maxsize = PHP_INT_MAX)
    {
        $this->maxsize = $maxsize;
    }

    /**
     * Ajoute un élément à la queue (bloque si plein).
     */
    public function put(mixed $item): \EorBah545\Asyncio\Promise
    {
        return new Promise(function ($resolve, $reject) use ($item) {
            $check = function () use ($resolve, $reject, $item, &$check) {
                if (count($this->queue) < $this->maxsize) {
                    $this->queue[] = $item;
                    // Réveille un consommateur en attente
                    if (!empty($this->resolvers)) {
                        $wake = array_shift($this->resolvers);
                        $wake();
                    }
                    $resolve(null);
                } else {
                    // Sature, on s’enregistre pour être réveillé plus tard
                    $this->resolvers[] = function () use ($check) {
                        $check();
                    };
                }
            };
            $check();
        });
    }

    /**
     * Retire un élément (bloque si vide).
     */
    public function get(): Promise
    {
        return new Promise(function ($resolve, $reject) {
            $check = function () use ($resolve, $reject, &$check) {
                if (!empty($this->queue)) {
                    $item = array_shift($this->queue);
                    // Réveille un producteur bloqué
                    if (!empty($this->resolvers)) {
                        $wake = array_shift($this->resolvers);
                        $wake();
                    }
                    $resolve($item);
                } else {
                    $this->resolvers[] = function () use ($check) {
                        $check();
                    };
                }
            };
            $check();
        });
    }
}