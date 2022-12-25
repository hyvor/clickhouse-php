<?php

it('updates session vars', function() {

    $clickhouse = test()->clickhouse;

    expect($clickhouse->sessionId())
        ->toBeString()
        ->toHaveLength(32);

    $response = $clickhouse->query("SELECT value FROM system.settings WHERE name = 'mutations_sync'");
    expect($response['data'][0][0])->toBe('0');

    $clickhouse->query('SET mutations_sync = 1');

    $response = $clickhouse->query("SELECT value FROM system.settings WHERE name = 'mutations_sync'");
    expect($response['data'][0][0])->toBe('1');

});