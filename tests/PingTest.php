<?php

namespace Hyvor\Clickhouse\Tests;

use Hyvor\Clickhouse\Clickhouse;
use Hyvor\Clickhouse\Exception\ClickhousePingException;

class PingTest extends TestCase
{

    public function testPings(): void
    {

        $clickhouse = new Clickhouse();
        $this->assertTrue($clickhouse->ping());

    }

    public function testPingsAndThrows(): void
    {
        $this->expectException(ClickhousePingException::class);

        $clickhouse = new Clickhouse(port: 1111);
        $clickhouse->pingThrow();
    }

}