<?php

use Hyvor\Clickhouse\Exception\ClickhouseException;

it('inserts a row', function() {

    addUsersTable();

    $clickhouse = test()->clickhouse;

    $response = $clickhouse->insert('users',
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

    expect($response)->toBeNull();

    $response = $clickhouse->query('SELECT * FROM users');


    $data = $response['data'];
    expect($response['data'])->toHaveCount(1);

    $row = $data[0];

    expect($row[0])->toBe(1)
        ->and($row[1])->toBe('2021-01-01 00:00:00')
        ->and($row[2])->toBe('John')
        ->and($row[3])->toBe(30);

});

it('inserts multiple', function() {

    addUsersTable();

    $clickhouse = test()->clickhouse;

    $clickhouse->insert('users',
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
    expect($response['data'])->toHaveCount(2);

});

it('checks column count', function() {

    addUsersTable();

    $clickhouse = test()->clickhouse;

    $response = $clickhouse->insert('users',
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

})->throws(ClickhouseException::class, 'Expected 4 columns, got 3');