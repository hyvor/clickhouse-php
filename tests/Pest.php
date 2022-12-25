<?php

use Hyvor\Clickhouse\Clickhouse;

uses()->beforeEach(function() {

    $this->clickhouse = new Clickhouse();

})->in('./');



function addUsersTable() {

    $clickhouse = test()->clickhouse;
    $clickhouse->query('DROP TABLE IF EXISTS users');

    $clickhouse->query('
        CREATE TABLE users (
            id UInt32,
            created_at DateTime,
            name String,
            age UInt8,
        ) ENGINE = MergeTree()
    ORDER BY id');

}