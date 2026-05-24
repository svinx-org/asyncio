<?php

use PHPUnit\Framework\TestCase;
use EorBah545\Examples\HttpServer;

class ServerTest extends TestCase
{
    public function testServerInstance(): void
    {
        $server = new HttpServer("Test");
        $this->assertInstanceOf(HttpServer::class, $server);
    }
}