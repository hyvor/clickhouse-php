<?php

namespace Hyvor\Clickhouse\Tests;

use Hyvor\Clickhouse\Clickhouse;

class InsertRawTest extends TestCase
{

    public function testInsertsRawWithoutParams(): void
    {

        $clickhouse = new Clickhouse();
        $this->createUsersTable($clickhouse);

        $response = $clickhouse->insertRaw(
            'users',
            ['id', 'created_at', 'name', 'age'],
            [
                [1, '2021-01-01 00:00:00', 'John', 30],
                [2, '2021-01-02 00:00:00', 'Jane', 25],
                [3, '2021-01-03 00:00:00', 'Doe', 35],
            ]
        );

        $response = $clickhouse->query('SELECT * FROM users');
        $this->assertIsArray($response);
        $this->assertIsArray($response['data']);
        $this->assertCount(3, $response['data']);

    }

}