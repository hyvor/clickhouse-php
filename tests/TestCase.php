<?php declare(strict_types=1);

namespace Hyvor\Clickhouse\Tests;

use Hyvor\Clickhouse\Clickhouse;

class TestCase extends \PHPUnit\Framework\TestCase
{

    public function createUsersTable(Clickhouse $clickhouse): void
    {

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

}