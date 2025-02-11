<?php

namespace Hyvor\Clickhouse\Tests;

use Hyvor\Clickhouse\Clickhouse;
use Hyvor\Clickhouse\Exception\ClickhouseHttpQueryException;

class QueryTest extends TestCase
{

    public function testCreatesATable(): void
    {

        $clickhouse = new Clickhouse();
        $this->createUsersTable($clickhouse);

        $createTable = $clickhouse->query('SHOW CREATE TABLE users');
        $this->assertIsArray($createTable);
        $this->assertIsArray($createTable['data']);
        $this->assertIsArray($createTable['data'][0]);
        $schema = $createTable['data'][0][0];
        $this->assertIsString($schema);

        $this->assertStringContainsString('CREATE TABLE default.users', $schema);
        $this->assertStringContainsString('`id` UInt32', $schema);
        $this->assertStringContainsString('`created_at` DateTime', $schema);
        $this->assertStringContainsString('`name` String', $schema);
        $this->assertStringContainsString('`age` UInt8', $schema);
        $this->assertStringContainsString('ENGINE = MergeTree', $schema);
        $this->assertStringContainsString('ORDER BY id', $schema);

    }

    public function testException(): void
    {
        $this->expectException(ClickhouseHttpQueryException::class);
        $clickhouse = new Clickhouse(port: 1011);
        $this->createUsersTable($clickhouse);
    }

}