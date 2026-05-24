# Asyncio – Async/Await pour PHP

[![PHP](https://img.shields.io/badge/PHP-8.1%2B-blue)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

**Asyncio** est une librairie asynchrone **100% PHP** sans dépendance externe.  
Elle apporte un vrai mécanisme `async`/`await` grâce aux [Fibers](https://www.php.net/manual/fr/language.fibers.php), une boucle d'événements, des promesses chaînables et des primitives de synchronisation (Queue, Lock, Event, Future).  
Un serveur HTTP asynchrone est inclus pour exécuter vos handlers de manière non‑bloquante.

---

## 🚀 Installation

```bash
composer require eorbah545/asyncio
```

**Ou clonez le dépôt et utilisez l’autoload de Composer :**

```bash
git clone https://github.com/EORBAH/asyncio.git
cd asyncio
composer dump-autoload
```

---

**✨ Fonctionnalités:**

- async / await natif – écrivez du code asynchrone comme en JavaScript.
- Boucle d’événements (scheduler) supportant les timers (sleep non‑bloquant) et les flux réseau (stream_select).
- Promesses (Promise) avec chaînage then / catch, await(), et méthodes statiques all, allSettled, race.
- Utilitaires : sleep, gather, wait, timeout, createTask, ErrorHandling.
- Primitives de synchronisation : Queue, Lock, Event, Future.
- Serveur HTTP asynchrone : définissez des routes GET/POST dont les callbacks peuvent être async. Gère plusieurs requêtes en parallèle sans blocage.
- Zéro dépendance – tout repose sur les fonctions natives de PHP 8.1+.

---

**📖 Exemple rapide:**

```php
use EorBah545\Asyncio\Asyncio;
use function EorBah545\Asyncio\await;

// Définit une tâche asynchrone
$task = Asyncio::async(function (string $name) {
    echo "[$name] Début\n";
    await(Asyncio::sleep(1.5));   // pause non‑bloquante
    echo "[$name] Fin\n";
    return "$name OK";
});

// Lance deux tâches en parallèle
$prom1 = $task("T1");
$prom2 = $task("T2");

$prom1->then(fn($res) => echo "$res\n");
$prom2->then(fn($res) => echo "$res\n");

// Exécute la boucle d'événements
Asyncio::run(function () {
    await(Asyncio::sleep(2)); // laisse le temps aux tâches
});
```

---

**🌐 Serveur HTTP asynchrone:**

```php
use EorBah545\Examples\HttpServer;
use EorBah545\Asyncio\Asyncio;
use function EorBah545\Asyncio\await;

$server = new HttpServer("MonApp");

$server->get('/', function ($request, Asyncio $async) {
    await($async::sleep(1)); // simulation d'un traitement long
    return ['message' => 'Hello World'];
});

$server->post('/data', function ($request, Asyncio $async) {
    $body = json_decode($request['body'], true);
    return ['received' => $body];
});

$server->run(8000);
```

Testez avec curl http://localhost:8000/ – la réponse arrive après 1 seconde, sans bloquer le serveur.

---

**🧩 Primitives de synchronisation:**

**Queue**

```php
$queue = new Queue(2);
await($queue->put($item));
$item = await($queue->get());
```

**Lock**

```php
$lock = new Lock();
await($lock->acquire());
// section critique
$lock->release();
```

**Event**

```php
$event = new Event();
await($event->wait()); // sera réveillé par $event->set()
$event->clear();
```

**Future**

```php
$future = new Future();
$future->set_result(42);
$result = await($future->await());
```

---

**📚 API**

**Asyncio**

Méthode Description
async(callable $fn): \Closure Transforme une fonction en coroutine retournant une Promise.
run(callable $coroutine): mixed Exécute une coroutine et démarre la boucle événementielle.
sleep(float $seconds): Promise Promesse résolue après $seconds secondes (non‑bloquant).
gather(array $promises): Promise Équivaut à Promise::all().
wait(array $promises): Promise Équivaut à Promise::allSettled().
timeout(callable $coroutine, float $seconds): Promise Rejette si la coroutine dépasse le délai.
ErrorHandling(callable $asyncFunc): \Closure Décore une fonction async pour capturer les erreurs et retourner null.
createTask(callable $coroutine): Promise Lance une coroutine dans une Fiber et retourne une promesse.

Promise

· then(?callable $onFulfilled, ?callable $onRejected): Promise
· catch(callable $onRejected): Promise
· await(): mixed (à utiliser uniquement dans une coroutine)
· Méthodes statiques : resolve($value), reject($reason), all($promises), allSettled($promises), race($promises)

Scheduler

· getInstance(): self
· run(callable $coroutine): void
· addTimer(float $seconds, callable $callback): void
· onReadable($stream, callable $callback): void
· onWritable($stream, callable $callback): void
· removeStream($stream): void

---

🤝 Contribuer

Les contributions sont les bienvenues !
Ouvrez une issue ou une pull request sur GitHub.

---

**Licence:**

Ce projet est sous licence MIT. Voir le fichier LICENSE.