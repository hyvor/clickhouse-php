<?php


use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Hyvor\Clickhouse\Clickhouse;

it('can inject a http client', function() {

    $mock = new MockHandler([
        new Response(404, [], 'Not Ok.'),
    ]);

    $client = new Client(['handler' => $mock]);
    $clickhouse = new Clickhouse(httpClient: $client);
    expect($clickhouse->ping())->toBeFalse();

});