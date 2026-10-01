<?php

namespace Hyvor\Clickhouse\Tests;

use GuzzleHttp\Psr7\HttpFactory;
use Hyvor\Clickhouse\Clickhouse;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\StreamFactoryInterface;

class LargeQueryTest extends TestCase
{

    /**
     * @return array<string, array{StreamFactoryInterface}>
     */
    public static function streamFactoryProvider(): array
    {
        return [
            'guzzle' => [new HttpFactory()],
            'nyholm' => [new Psr17Factory()],
        ];
    }

    /**
     * Queries used to be sent as a multipart form field, which broke large queries:
     *
     * - Nyholm creates streams of strings >= 200000 bytes with a custom stream wrapper
     *   (uri "Nyholm-Psr7-Zval://"). MultipartStreamBuilder used that uri as the filename
     *   of the "query" part, and ClickHouse treats any multipart part with a filename as an
     *   external table, failing with:
     *   "Neither structure nor types have not been provided for external table query"
     * - Other factories failed with "HTML Form Exception: Field value too long"
     *
     * This happened in Hyvor Talk's clickhouse:sync:work, which calls insertRaw()
     * with up to 1000 sync records in a single query.
     */
    #[DataProvider('streamFactoryProvider')]
    public function testInsertRawWithLargeQuery(StreamFactoryInterface $streamFactory): void
    {

        $clickhouse = new Clickhouse(httpStreamFactory: $streamFactory);
        $this->createUsersTable($clickhouse);

        $rows = [];
        for ($i = 1; $i <= 5000; $i++) {
            $rows[] = [$i, '2021-01-01 00:00:00', 'user_name_' . $i, 30];
        }

        $clickhouse->insertRaw(
            'users',
            ['id', 'created_at', 'name', 'age'],
            $rows
        );

        $count = $clickhouse->select('SELECT count() FROM users')->value();
        $this->assertSame('5000', $count);

    }

}
