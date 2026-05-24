<?php

namespace EorBah545\Asyncio\Examples;

use EorBah545\Asyncio\Asyncio;
use EorBah545\Asyncio\Promise;
use EorBah545\Asyncio\Scheduler;
use function EorBah545\Asyncio\await;

class HttpServer
{
    private string $name;
    private array $routes = [];
    private $serverSocket;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Enregistre une route GET.
     * Le callback reçoit ($request, $asyncio) et doit retourner un tableau qui sera converti en JSON.
     */
    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    /**
     * Enregistre une route POST.
     */
    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    /**
     * Démarre le serveur sur le port $port.
     */
    public function run(int $port): void
    {
        $address = "0.0.0.0:$port";
        $this->serverSocket = stream_socket_server("tcp://$address", $errno, $errstr);
        if (!$this->serverSocket) {
            throw new \RuntimeException("Impossible de lancer le serveur : $errstr ($errno)");
        }
        stream_set_blocking($this->serverSocket, false);
        echo "Serveur \"{$this->name}\" en écoute sur http://$address\n";

        $scheduler = Scheduler::getInstance();

        // On enregistre le socket serveur pour accepter les connexions entrantes
        $scheduler->onReadable($this->serverSocket, function ($serverSocket) use ($scheduler) {
            $clientSocket = stream_socket_accept($serverSocket, 0);
            if ($clientSocket) {
                stream_set_blocking($clientSocket, false);
                $this->handleClient($clientSocket);
            }
        });

        // On lance la boucle d'événements avec une coroutine vide qui ne fait rien.
        // La boucle tournera grâce aux streams et fibres.
        $scheduler->run(function () {});
    }

    /**
     * Gère un nouveau client : lit la requête, exécute le callback, envoie la réponse.
     */
    private function handleClient($clientSocket): void
    {
        $request = '';
        $scheduler = Scheduler::getInstance();

        // On surveille le socket client en lecture pour lire la requête par morceaux
        $scheduler->onReadable($clientSocket, function ($stream) use (&$request, $clientSocket, $scheduler) {
            $data = fread($stream, 8192);
            if ($data === false || $data === '') {
                // Connexion fermée
                $scheduler->removeStream($clientSocket);
                fclose($clientSocket);
                return;
            }
            $request .= $data;

            // Si on a reçu la fin des headers (double CRLF), on arrête de lire et on traite
            if (strpos($request, "\r\n\r\n") !== false) {
                $scheduler->removeStream($clientSocket); // ne plus surveiller ce socket
                $this->processRequest($clientSocket, $request);
            }
        });
    }

    /**
     * Parse la requête HTTP, appelle le handler approprié, renvoie la réponse.
     */
    private function processRequest($clientSocket, string $rawRequest): void
    {
        // Parse basique
        $lines = explode("\r\n", $rawRequest);
        $requestLine = explode(' ', $lines[0] ?? '');
        $method = strtoupper($requestLine[0] ?? 'GET');
        $path = $requestLine[1] ?? '/';

        // Extrait le corps (pour POST)
        $body = '';
        $headerEnd = strpos($rawRequest, "\r\n\r\n");
        if ($headerEnd !== false) {
            $body = substr($rawRequest, $headerEnd + 4);
        }

        // Cherche un handler
        $handler = $this->routes[$method][$path] ?? null;
        if (!$handler) {
            $handler = function () { return ['error' => 'Not Found', 'code' => 404]; };
        }

        // Construit un objet $request
        $request = [
            'method' => $method,
            'path' => $path,
            'body' => $body,
            'headers' => $this->parseHeaders($rawRequest),
        ];

        // On lance le handler dans une coroutine
        $promise = Asyncio::createTask(function () use ($handler, $request) {
            try {
                $result = $handler($request, new Asyncio());
                if ($result instanceof Promise) {
                    $result = await($result);
                }
                return $result;
            } catch (\Throwable $e) {
                return ['error' => $e->getMessage(), 'code' => 500];
            }
        });

        // Quand le handler a fini, on envoie la réponse
        $promise->then(function ($result) use ($clientSocket) {
            $this->sendResponse($clientSocket, $result);
        })->catch(function ($reason) use ($clientSocket) {
            $this->sendResponse($clientSocket, ['error' => (string)$reason, 'code' => 500]);
        });
    }

    private function parseHeaders(string $raw): array
    {
        $headers = [];
        foreach (explode("\r\n", $raw) as $line) {
            if (strpos($line, ':') !== false) {
                [$name, $value] = explode(':', $line, 2);
                $headers[trim($name)] = trim($value);
            }
        }
        return $headers;
    }

    private function sendResponse($clientSocket, $data): void
    {
        if (is_array($data) && isset($data['error'])) {
            $code = $data['code'] ?? 500;
            $body = json_encode($data);
        } else {
            $code = 200;
            $body = json_encode($data);
        }

        $response = "HTTP/1.1 $code OK\r\n";
        $response .= "Content-Type: application/json\r\n";
        $response .= "Connection: close\r\n";
        $response .= "Content-Length: " . strlen($body) . "\r\n";
        $response .= "\r\n";
        $response .= $body;

        fwrite($clientSocket, $response);
        fclose($clientSocket);
    }
}