<?php

namespace Hyvor\Clickhouse;

use GuzzleHttp\Client as HttpClient;

class Clickhouse
{

    private HttpClient $http;

    private string $sessionId;

    public function __construct(
        private string $host = 'localhost',
        private int $port = 8123,
        private string $user = 'default',
        private string $password = '',
        private ?string $database = 'default',
    )
    {
        $this->http = new HttpClient();
        $this->sessionId = bin2hex(random_bytes(16));
    }

    public function insert(string $table, array $rows)
    {



    }

    public function query($query, $bindings = [])
    {

        $host = $this->host;
        $port = $this->port;

        $url = 'http://' . $host . ':' . $port;

        $paramsMultipart = [];
        foreach ($bindings as $key => $value) {
            $paramsMultipart[] = [
                'name' => 'param_' . $key,
                'contents' => $value
            ];
        }

        $response = $this->http->post($url, [
            'headers' => [
                'X-ClickHouse-Format' => 'JSONCompact',
                'X-ClickHouse-User' => $this->user,
                'X-ClickHouse-Key' => $this->password,
                'X-ClickHouse-Database' => $this->database,
            ],
            'multipart' => [
                [
                    'name' => 'query',
                    'contents' => $query
                ],
                ...$paramsMultipart
            ],
            // 'debug' => true,
        ]);

        dd(json_decode($response->getBody()->getContents()));

    }

}