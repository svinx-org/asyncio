<?php

namespace EorBah545\Asyncio;

class Event
{
    private bool $isSet = false;
    private array $queue = [];   // file de resolveurs

    /**
     * Attend que l'événement soit déclenché.
     * Si déjà déclenché, retourne une promesse immédiatement résolue.
     */
    public function wait(): Promise
    {
        if ($this->isSet) {
            return Promise::resolve(null);
        }

        $resolver = null;
        $promise = new Promise(function ($resolve) use (&$resolver) {
            $resolver = $resolve;
        });
        $this->queue[] = $resolver;

        return $promise;
    }

    /**
     * Déclenche l'événement : toutes les coroutines en attente sont réveillées.
     */
    public function set(): void
    {
        $this->isSet = true;

        while (!empty($this->queue)) {
            $resolve = array_shift($this->queue);
            $resolve(null);
        }
    }

    /**
     * Réinitialise l'événement pour qu'il puisse être réutilisé.
     */
    public function clear(): void
    {
        $this->isSet = false;
    }
}