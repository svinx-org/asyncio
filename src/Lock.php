<?php

namespace EorBah545\Asyncio;

class Lock
{
    private bool $locked = false;
    private array $queue = [];

    /**
     * Acquiert le verrou (attend s'il est déjà tenu).
     * Retourne une promesse résolue lorsque le verrou est obtenu.
     */
    public function acquire(): Promise
    {
        if (!$this->locked) {
            $this->locked = true;
            return Promise::resolve(null);
        }

        // On doit attendre : on crée une promesse et on garde le resolveur en file d'attente
        $resolver = null;
        $promise = new Promise(function ($resolve) use (&$resolver) {
            $resolver = $resolve;
        });
        $this->queue[] = $resolver;

        return $promise;
    }

    /**
     * Relâche le verrou. Réveille le prochain acquéreur s'il y en a un.
     */
    public function release(): void
    {
        if (!empty($this->queue)) {
            // Réveille le premier en attente (le verrou reste "tenu" pour lui)
            $resolve = array_shift($this->queue);
            $resolve(null);
        } else {
            // Personne n'attend, on libère complètement
            $this->locked = false;
        }
    }
}