<?php

namespace Hyvor\Clickhouse;

use GuzzleHttp\Client;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request;
use Hyvor\Clickhouse\Exception\ClickhouseHttpQueryException;
use Hyvor\Clickhouse\Exception\ClickhousePingException;
use Hyvor\Clickhouse\Result\ResultSet;
use Psr\Http\Client\ClientInterface;

class Clickhouse
{

    private ClientInterface $http;

    private string $sessionId;

    public function __construct(
        private string $host = 'localhost',
        private int $port = 8123,
        private string $user = 'default',
        private string $password = '',
        private ?string $database = 'default',

        // dependencies
        ClientInterface $httpClient = null,
    )
    {
        $this->http = $httpClient ?? new HttpClient();
        $this->sessionId = bin2hex(random_bytes(16));
    }

    public function ping() : bool
    {

        try {

            $request = new Request('GET', $this->getUrl('/ping'));
            $response = $this->http->sendRequest($request);

            if ($response->getStatusCode() !== 200) {
                return false;
            }

            $body = $response->getBody()->getContents();

            return $body === "Ok.\n";

        } catch (GuzzleException $e) {
            return false;
        }

    }

    public function pingThrow()
    {

        if (!$this->ping())
            throw new ClickhousePingException;

    }

    public function insert(string $table, array $columns, array ...$rows) : mixed
    {

        $columnNames = implode(', ', array_keys($columns));

        $bindings = [];
        $placeholders = [];
        foreach ($rows as $i => $row) {

            $rowPlaceholder = [];

            foreach ($row as $index => $value) {

                $key = 'r' . $i . '_' . array_keys($columns)[$index];
                $bindings[$key] = $value;

                $rowPlaceholder[] = '{' . $key . ':' . $columns[array_keys($columns)[$index]] . '}';
            }

            $rowPlaceholder = '(' . implode(',', $rowPlaceholder) . ')';
            $placeholders[] = $rowPlaceholder;

        }

        $placeholders = implode(',', $placeholders);

        $query = "INSERT INTO $table ($columnNames) VALUES $placeholders";

        return $this->query($query, $bindings);

    }

    public function select(string $query, array $bindings = []) : ResultSet
    {
        $response = $this->query($query, $bindings);
        return new ResultSet($response);
    }

    /**
     * @param array<string, mixed> $bindings
     * @throws ClickhouseHttpQueryException
     */
    public function query(string $query, array $bindings = []) : mixed
    {

        // session_id doesn't work as a POST param
        $getQuery = http_build_query([
            'session_id' => $this->sessionId
        ]);

        $url = $this->getUrl() . '/?' . $getQuery;

        $paramsMultipart = [];
        foreach ($bindings as $key => $value) {
            $paramsMultipart[] = [
                'name' => 'param_' . $key,
                'contents' => $value
            ];
        }

        try {

            $request = new Request(
                'POST',
                $url,
                [
                    'X-ClickHouse-Format' => 'JSONCompact',
                    'X-ClickHouse-User' => $this->user,
                    'X-ClickHouse-Key' => $this->password,
                    'X-ClickHouse-Database' => $this->database,
                ],
                new MultipartStream([
                    [
                        'name' => 'query',
                        'contents' => $query
                    ],
                    ...$paramsMultipart
                ])
            );

            $response = $this->http->sendRequest($request);

        } catch (GuzzleException $exception) {
            throw new ClickhouseHttpQueryException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }

        $json = $response->getBody()->getContents();

        return json_decode($json, true);

    }

    public function sessionId() : string
    {
        return $this->sessionId;
    }

    private function getUrl(string $append = '') : string
    {
        return 'http://' . $this->host . ':' . $this->port . $append;
    }

}