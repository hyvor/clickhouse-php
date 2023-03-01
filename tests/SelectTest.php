<?php

use Hyvor\Clickhouse\Clickhouse;
use Hyvor\Clickhouse\Result\ResultSet;

it('connection', function () {

    addUsersTable();

    $clickhouse = test()->clickhouse;

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

    /** @var ResultSet $results */
    $results = $clickhouse->select('SELECT * FROM users LIMIT 6');

    expect($results->rows)->toBe(6)
        ->and($results->count())->toBe(6)
        ->and($results->rowsBeforeLimitAtLeast)->toBe(7)
        ->and($results->elapsedTimeSeconds)->toBeGreaterThan(0)
        ->and($results->rowsRead)->toBe(7)
        ->and($results->bytesRead)->toBeGreaterThan(0)
        ->and($results->all())->toHaveCount(6)
        ->and($results->first()['id'])->toBe(1);

    // can loop
    foreach ($results->all() as $row) {
        expect($row['id'])->toBeInt();
    }

    $count = $clickhouse->select('SELECT COUNT(*) FROM users');

    expect($count->value())->toBe('7');

});
