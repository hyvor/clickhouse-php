<?php

namespace Hyvor\Clickhouse\Tests;

use Hyvor\Clickhouse\Clickhouse;

class SessionTest extends TestCase
{

    public function testUpdatesSessionVars(): void
    {

        $clickhouse = new Clickhouse();

        $sessionId = $clickhouse->sessionId();
        $this->assertIsString($sessionId);
        $this->assertSame(32, strlen($sessionId));

        $response = $clickhouse->query("SELECT value FROM system.settings WHERE name = 'mutations_sync'");
        $this->assertIsArray($response);
        $this->assertIsArray($response['data']);
        $this->assertIsArray($response['data'][0]);
        $this->assertSame('0', $response['data'][0][0]);

        $clickhouse->query('SET mutations_sync = 1');

        $response = $clickhouse->query("SELECT value FROM system.settings WHERE name = 'mutations_sync'");
        $this->assertIsArray($response);
        $this->assertIsArray($response['data']);
        $this->assertIsArray($response['data'][0]);
        $this->assertSame('1', $response['data'][0][0]);

    }

}