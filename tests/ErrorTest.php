<?php

use Hyvor\Clickhouse\Exception\ClickhouseHttpQueryException;

it('handles query errors', function() {

    $clickhouse = test()->clickhouse;

    $clickhouse->query('SELECT something FROM users');

})->throws(ClickhouseHttpQueryException::class, "Missing columns: 'something'");