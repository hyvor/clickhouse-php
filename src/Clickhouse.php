<?php

namespace Hyvor\Clickhouse;

use GuzzleHttp\Client;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request;
use Hyvor\Clickhouse\Exception\ClickhouseException;
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

    public function pingThrow() : void
    {

        if (!$this->ping())
            throw new ClickhousePingException;

    }

    /**
     * @param array<string, string> $columns
     * @param array<string, mixed> ...$rows
     * @throws ClickhouseHttpQueryException
     */
    public function insert(string $table, array $columns, array ...$rows) : mixed
    {

        $columnNames = array_keys($columns);
        sort($columnNames);
        $columnNames = implode(',', $columnNames);

        $bindings = [];
        $placeholders = [];
        foreach ($rows as $i => $row) {

            $rowPlaceholder = [];

            if (count($row) !== count($columns)) {
                throw new ClickhouseException(
                    'Invalid row. Expected ' .
                    count($columns) . ' columns, got ' .
                    count($row) .
                    ' columns.'
                );
            }

            ksort($row);

            foreach ($row as $columnName => $value) {
                $key = 'r' . $i . '_' . $columnName;
                $bindings[$key] = $value;
                $rowPlaceholder[] = '{' . $key . ':' . $columns[$columnName] . '}';
            }

            $rowPlaceholder = '(' . implode(',', $rowPlaceholder) . ')';
            $placeholders[] = $rowPlaceholder;

        }

        $placeholders = implode(',', $placeholders);

        $query = "INSERT INTO $table ($columnNames) VALUES $placeholders";

        return $this->query($query, $bindings);

    }

    /**
     * @param string[] $columns
     * @param array<array<mixed>> $rows
     * @throws ClickhouseHttpQueryException
     */
    public function insertRaw(
        string $table,
        array $columns,
        array $rows,
        bool $asyncInsert = true,
        bool $waitForAsyncInsert = true
    ) : mixed
    {

        $asyncInsert = $asyncInsert ? 1 : 0;
        $waitForAsyncInsert = $waitForAsyncInsert ? 1 : 0;

        $columns = implode(',', $columns);

        $values = [];
        foreach ($rows as $row) {

            foreach ($row as $key => $value) {
                if (is_string($value)) {
                    $row[$key] = "'$value'";
                }
            }

            $values[] = '(' . implode(',', $row) . ')';

        }

        $values = implode(',', $values);

        $query = "INSERT INTO $table ($columns) SETTINGS async_insert=$asyncInsert, wait_for_async_insert=$waitForAsyncInsert VALUES $values";

        return $this->query($query);
    }

    /**
     * @param array<string, mixed> $bindings
     */
    public function select(string $query, array $bindings = []) : ResultSet
    {
        $response = $this->query($query, $bindings);

        if (!is_array($response)) {
            throw new ClickhouseHttpQueryException('Invalid query response. Expected array, got ' . gettype($response));
        }

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

            $headers = [
                'X-ClickHouse-Format' => 'JSONCompact',
                'X-ClickHouse-User' => $this->user,
                'X-ClickHouse-Key' => $this->password,
            ];

            if ($this->database) {
                $headers['X-ClickHouse-Database'] = $this->database;
            }

            $request = new Request(
                'POST',
                $url,
                $headers,
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

        $body = $response->getBody()->getContents();

        if ($response->getStatusCode() !== 200) {
            throw new ClickhouseHttpQueryException($body, $response->getStatusCode());
        }

        return json_decode($body, true);

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