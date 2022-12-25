<?php

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
            1,
            '2021-01-01 00:00:00',
            'John',
            30,
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
            1,
            '2021-01-01 00:00:00',
            'John',
            30,
        ],
        [
            2,
            '2021-01-01 00:00:00',
            'Jane',
            25,
        ]
    );

    $response = $clickhouse->query('SELECT * FROM users');
    expect($response['data'])->toHaveCount(2);

});