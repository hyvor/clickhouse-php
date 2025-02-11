<?php

namespace Hyvor\Clickhouse\Tests;

use Hyvor\Clickhouse\Clickhouse;
use Hyvor\Clickhouse\Exception\ClickhouseException;

class InsertTest extends TestCase
{

    public function testInsertsARow(): void
    {

        $clickhouse = new Clickhouse();
        $this->createUsersTable($clickhouse);

        $response = $clickhouse->insert(
            'users',
            [
                'id' => 'UInt32',
                'created_at' => 'DateTime',
                'name' => 'String',
                'age' => 'UInt8',
            ],
            [
                'id' => 1,
                'created_at' => '2021-01-01 00:00:00',
                'name' => 'John',
                'age' => 30,
            ]
        );

        $this->assertNull($response);

        $response = $clickhouse->query('SELECT * FROM users');

        $this->assertIsArray($response);
        $this->assertIsArray($response['data']);
        $this->assertCount(1, $response['data']);

        $row = $response['data'][0];

        $this->assertIsArray($row);
        $this->assertEquals(1, $row[0]);
        $this->assertEquals('2021-01-01 00:00:00', $row[1]);
        $this->assertEquals('John', $row[2]);
        $this->assertEquals(30, $row[3]);

    }

    public function testInsertsMultiple(): void
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
            [
                'id' => 1,
                'created_at' => '2021-01-01 00:00:00',
                'name' => 'John',
                'age' => 30,
            ],
            [
                'id' => 2,
                'created_at' => '2021-01-01 00:00:00',
                'name' => 'Jane',
                'age' => 25,
            ]
        );

        $response = $clickhouse->query('SELECT * FROM users');
        $this->assertIsArray($response);
        $this->assertIsArray($response['data']);
        $this->assertCount(2, $response['data']);

    }

    public function testChecksColumnCount(): void
    {

        $clickhouse = new Clickhouse();
        $this->createUsersTable($clickhouse);

        $this->expectException(ClickhouseException::class);
        $this->expectExceptionMessage('Expected 4 columns, got 3');

        $clickhouse->insert(
            'users',
            [
                'id' => 'UInt32',
                'created_at' => 'DateTime',
                'name' => 'String',
                'age' => 'UInt8',
            ],
            [
                'id' => 1,
                'created_at' => '2021-01-01 00:00:00',
                'name' => 'John',
            ]
        );

    }


    # bug
    public function testInsertsCorrectlyWhenTheRowKeyOrderIsDifferent(): void
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
            [
                'age' => 10,
                'created_at' => '2021-01-01 00:00:00',
                'name' => 'John',
                'id' => 1
            ]
        );

        $response = $clickhouse->select('SELECT * FROM users');
        $row = $response->first();

        $this->assertIsArray($row);
        $this->assertEquals(1, $row['id']);
        $this->assertEquals(10, $row['age']);

    }

}