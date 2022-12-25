<?php

use Hyvor\Clickhouse\Clickhouse;
use Hyvor\Clickhouse\Exception\ClickhousePingException;

it('pings', function() {
    expect(test()->clickhouse->ping())->toBeTrue();
});

it('pings and throws', function() {
    $clickhouse = new Clickhouse(port: 1111);
    $clickhouse->pingThrow();
})->throws(ClickhousePingException::class);