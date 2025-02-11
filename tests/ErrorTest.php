<?php declare(strict_types=1);

namespace Hyvor\Clickhouse\Tests;

use Hyvor\Clickhouse\Clickhouse;
use Hyvor\Clickhouse\Exception\ClickhouseHttpQueryException;

class ErrorTest extends TestCase
{

    public function testHandlesQueryErrors(): void
    {

        $this->expectException(ClickhouseHttpQueryException::class);
        $this->expectExceptionMessage("Missing columns: 'something'");

        $clickhouse = new Clickhouse();
        $this->createUsersTable($clickhouse);
        $clickhouse->query('SELECT something FROM users');

    }

}