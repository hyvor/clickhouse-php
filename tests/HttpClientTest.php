<?php

namespace Hyvor\Clickhouse\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Hyvor\Clickhouse\Clickhouse;

class HttpClientTest extends TestCase
{

    public function testInjectingHttpClient(): void
    {


        $mock = new MockHandler([
            new Response(404, [], 'Not Ok.'),
        ]);

        $client = new Client(['handler' => $mock]);
        $clickhouse = new Clickhouse(httpClient: $client);
        $this->assertFalse($clickhouse->ping());

    }

}