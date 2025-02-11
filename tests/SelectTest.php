<?php

namespace Hyvor\Clickhouse\Tests;

use Hyvor\Clickhouse\Clickhouse;

class SelectTest extends TestCase
{

    public function testConnection(): void
    {

        $clickhouse = new Clickhouse();
        $this->createUsersTable($clickhouse);

        $clickhouse->insert(
            'users',
            [
                'id' => 'UInt32',
                'created_at' => 'DateTime',
                'name' => 'String',
                'age' => 'UInt8',
            ],
            ['id' => 1, 'created_at' => '2021-01-01 00:00:00', 'name' => 'John', 'age' => 30],
            ['id' => 2, 'created_at' => '2021-01-01 00:00:00', 'name' => 'Jane', 'age' => 25],
            ['id' => 3, 'created_at' => '2021-01-01 00:00:00', 'name' => 'Jack', 'age' => 20],
            ['id' => 4, 'created_at' => '2021-01-01 00:00:00', 'name' => 'Jill', 'age' => 15],
            ['id' => 5, 'created_at' => '2021-01-01 00:00:00', 'name' => 'Jenny', 'age' => 10],
            ['id' => 6, 'created_at' => '2021-01-01 00:00:00', 'name' => 'Jen', 'age' => 5],
            ['id' => 7, 'created_at' => '2021-01-01 00:00:00', 'name' => 'Jen', 'age' => 5],
        );

        $results = $clickhouse->select('SELECT * FROM users LIMIT 6');

        $this->assertSame(6, $results->rows);
        $this->assertSame(6, $results->count());
        $this->assertGreaterThanOrEqual(7, $results->rowsBeforeLimitAtLeast);
        $this->assertGreaterThan(0, $results->elapsedTimeSeconds);
        $this->assertSame(7, $results->rowsRead);
        $this->assertGreaterThan(0, $results->bytesRead);
        $this->assertCount(6, $results->all());
        $this->assertSame(1, $results->first()['id'] ?? null);

        foreach ($results->all() as $row) {
            $this->assertIsArray($row);
            $this->assertIsInt($row['id']);
        }

        $count = $clickhouse->select('SELECT COUNT(*) FROM users');
        $this->assertSame('7', $count->value());

    }

}
