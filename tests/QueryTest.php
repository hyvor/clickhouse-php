<?php

use Hyvor\Clickhouse\Clickhouse;

it('creates a table', function() {

    addUsersTable();

    $clickhouse = test()->clickhouse;

    $createTable = $clickhouse->query('SHOW CREATE TABLE users');
    $schema = $createTable['data'][0][0];

    expect($schema)
        ->toContain('CREATE TABLE default.users')
        ->toContain('`id` UInt32')
        ->toContain('`created_at` DateTime')
        ->toContain('`name` String')
        ->toContain('`age` UInt8')
        ->toContain('ENGINE = MergeTree')
        ->toContain('ORDER BY id');

});