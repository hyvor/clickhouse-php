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
        [1, '2021-01-01 00:00:00', 'John', 30,],
        [2, '2021-01-01 00:00:00', 'Jane', 25,],
        [3, '2021-01-01 00:00:00', 'Jack', 20,],
        [4, '2021-01-01 00:00:00', 'Jill', 15,],
        [5, '2021-01-01 00:00:00', 'Jenny', 10,],
        [6, '2021-01-01 00:00:00', 'Jen', 5,],
        [7, '2021-01-01 00:00:00', 'Jen', 5,],
    );

    /** @var ResultSet $results */
    $results = $clickhouse->select('SELECT * FROM users LIMIT 6');

    expect($results->rows)->toBe(6)
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
