<div align="center">
  <h1>Async/await for PHP</h1>
  <p>Promises Async/await for PHP 8.1+ using Fibers.</p>
  <img src="https://img.shields.io/badge/PHP-8.1%2B-blue" alt="PHP Version" />
  <br>
  <img src="https://img.shields.io/badge/license/Apache-2.0%2B-green" alt="License" />
</div>

<br>

**Asyncio** est une librairie asynchrone **100% PHP** sans dépendance externe.
Elle apporte un mécanisme `async`/`await` grâce aux [Fibers](https://www.php.net/manual/fr/language.fibers.php), une boucle d'événements, des promesses chaînables et des primitives de synchronisation (Queue, Lock, Event, Future).  
Un serveur HTTP asynchrone est inclus pour exécuter vos handlers de manière non‑bloquante en guise d'exemple.

## Documentation

Une documentation complète est disponible au [dossier docs](/docs/Index.md). Explorez des guides détaillés, des exemples et des tutoriels pour tirer le meilleur parti de la library.

## Installation

Install depuis packagist avec composer.

```bash
composer require eor_bah545/asyncio
```

Vous pouvez egalement utiliser github

```bash
git clone https://github.com/phoenixshareplus/asyncio.git
cd asyncio
composer dump-autoload
```

### Requirements
PHP 8.1 ou une version supérieur est requis pour utiliser la library `asyncio` pour un support des Fibers.

## Usage

La librairy asyncio prend en charge a la fois l'asynchrone et le synchrone. Pour un ensemble complet d'exemples illustrant les capacités de la librairy, notamment les promises, les events, et bien plus encore, reportez vous au [dossier examples](/examples).

### Asyncio Utilisation

Initialez des processus async/await en créer des fonction non blockante avec un support de `then` et `catch` pour capturer les reponse ou les erreur sans 
bloquer l'execution du code et on peut definir des tache en parallèle.

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

$prom1->then(function($res) {
    echo "$res\n"; 
});
$prom2->then(function($res) { 
    echo "$res\n"; 
});

// Exécute la boucle d'événements
Asyncio::run(function () {
    await(Asyncio::sleep(2)); // laisse le temps aux tâches
});
```

Si vous utilisez notre server defini dans les exemple alors nous pourriez definir des methodes `get` ou `post` avec des fonction totalement asynchrone qui ne bloque pas le threads.

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

Une fois le script executer avec php le serveur srra mus en marche sur le port 8000, ensuite testez avec curl http://localhost:8000/ – la réponse arrive après 1 seconde, sans bloquer le serveur.

## Primitives de synchronisation:

### Queue

```php
$queue = new Queue(2);
await($queue->put($item));
$item = await($queue->get());
```

Il faut absolument comprendre que l'utilisation d'`await` hors d'une fonction asynchrone defini par `Asyncion::run` genere automatiquement une exception.

### Lock

```php
$lock = new Lock();
await($lock->acquire());
// section critique
$lock->release();
```

### Event

```php
$event = new Event();
await($event->wait()); // sera réveillé par $event->set()
$event->clear();
```

### Future

```php
$future = new Future();
$future->set_result(42);
$result = await($future->await());
```

## Advanced Features

### Asyncio

- `async(callable $fn)`: \Closure Transforme une fonction en coroutine retournant une Promise.
- `run(callable $coroutine)`: mixed Exécute une coroutine et démarre la boucle événementielle.
- `sleep(float $seconds)`: Promise Promesse résolue après $seconds secondes (non‑bloquant).
- `gather(array $promises)`: Promise Équivaut à Promise::all().
- `wait(array $promises)`: Promise Équivaut à Promise::allSettled().
- `timeout(callable $coroutine, float $seconds)`: Promise Rejette si la coroutine dépasse le délai.
- `ErrorHandling(callable $asyncFunc)`: \Closure Décore une fonction async pour capturer les erreurs et retourner null.
- `createTask(callable $coroutine)`: Promise Lance une coroutine dans une Fiber et retourne une promesse.

### Promise

- `then(?callable $onFulfilled, ?callable $onRejected)`: Promise
- `catch(callable $onRejected)`: Promise
- `await()`: mixed (à utiliser uniquement dans une coroutine)
- Méthodes statiques : `resolve($value)`, `reject($reason)`, `all($promises)`, `allSettled($promises)`, `race($promises)`

### Scheduler

- `getInstance(): self`
- `run(callable $coroutine): void`
- `addTimer(float $seconds, callable $callback): void`
- `onReadable($stream, callable $callback): void`
- `onWritable($stream, callable $callback): void`
- `removeStream($stream): void`

## License

La librairy Asyncio est distribué sous la license Apache-2.0.

## Contributing

Consulter la [documentation](/CONTRIBUTING.md) sur la contribution à ce projet.
