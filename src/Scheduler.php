<?php

namespace EorBah545\Asyncio;

class Scheduler
{
    private static ?self $instance = null;
    private array $fibers = [];
    private int $activeFibers = 0;
    private array $timers = [];
    private array $readStreams = [];
    private array $writeStreams = [];
    private bool $running = false;

    private function __construct() {}
    
    public function reset(): void
    {
        $this->fibers = [];
        $this->activeFibers = 0;
        $this->timers = [];
        $this->readStreams = [];
        $this->writeStreams = [];
        $this->running = false;
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function spawn(\Fiber $fiber): void
    {
        $this->activeFibers++;
        $this->fibers[] = [$fiber, null, null];
    }

    public function enqueue(\Fiber $fiber, mixed $value = null, ?\Throwable $exception = null): void
    {
        $this->fibers[] = [$fiber, $value, $exception];
    }

    public function addTimer(float $seconds, callable $callback): void
    {
        $expire = microtime(true) + $seconds;
        $this->timers[] = ['expire' => $expire, 'callback' => $callback];
        usort($this->timers, fn($a, $b) => $a['expire'] <=> $b['expire']);
    }

    public function onReadable($stream, callable $callback): void
    {
        $this->readStreams[(int)$stream] = ['stream' => $stream, 'callback' => $callback];
    }

    public function onWritable($stream, callable $callback): void
    {
        $this->writeStreams[(int)$stream] = ['stream' => $stream, 'callback' => $callback];
    }

    public function removeStream($stream): void
    {
        $id = (int)$stream;
        unset($this->readStreams[$id], $this->writeStreams[$id]);
    }

    public function run(callable $coroutine): void
    {
        $main = new \Fiber($coroutine);
        $this->spawn($main);

        $this->running = true;
        while ($this->running && ($this->activeFibers > 0 || !empty($this->readStreams) || !empty($this->writeStreams) || !empty($this->timers))) {
            // --- Traiter les timers expirés ---
            $now = microtime(true);
            while (!empty($this->timers) && $this->timers[0]['expire'] <= $now) {
                $timer = array_shift($this->timers);
                $timer['callback']();
                $now = microtime(true);
            }

            // --- Traiter les fibres prêtes ---
            while (!empty($this->fibers)) {
                [$current, $value, $exception] = array_shift($this->fibers);
                try {
                    if ($exception) {
                        $current->throw($exception);
                    } elseif ($current->isStarted()) {
                        $current->resume($value);
                    } else {
                        $current->start();
                    }
                } catch (\Throwable $e) {
                    $this->activeFibers--;
                    throw $e;
                }
                if ($current->isTerminated()) {
                    $this->activeFibers--;
                }
            }

            // Si tout est terminé, on sort
            if ($this->activeFibers === 0 && empty($this->readStreams) && empty($this->writeStreams) && empty($this->timers)) {
                break;
            }

            // --- Préparer l'attente sur les flux et les timers ---
            $read = array_map(fn($e) => $e['stream'], $this->readStreams);
            $write = array_map(fn($e) => $e['stream'], $this->writeStreams);
            $except = [];
            $timeout = null;

            if (!empty($this->timers)) {
                $timeout = max(0, $this->timers[0]['expire'] - microtime(true));
            } else {
                // Pas de timer : dormir un peu pour éviter de saturer le CPU
                $timeout = 0.1;
            }

            if (!empty($read) || !empty($write)) {
                stream_select($read, $write, $except, (int)$timeout, (int)(($timeout - (int)$timeout) * 1_000_000));
                foreach ($read as $stream) {
                    $id = (int)$stream;
                    if (isset($this->readStreams[$id])) {
                        $cb = $this->readStreams[$id]['callback'];
                        $cb($stream);
                    }
                }
                foreach ($write as $stream) {
                    $id = (int)$stream;
                    if (isset($this->writeStreams[$id])) {
                        $cb = $this->writeStreams[$id]['callback'];
                        $cb($stream);
                    }
                }
            } else {
                usleep((int)($timeout * 1_000_000));
            }
        }
    }
}