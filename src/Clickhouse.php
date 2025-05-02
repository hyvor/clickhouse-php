<?php

namespace Hyvor\Clickhouse;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Http\Message\MultipartStream\MultipartStreamBuilder;
use Hyvor\Clickhouse\Exception\ClickhouseException;
use Hyvor\Clickhouse\Exception\ClickhouseHttpQueryException;
use Hyvor\Clickhouse\Exception\ClickhousePingException;
use Hyvor\Clickhouse\Result\ResultSet;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class Clickhouse
{

    private ClientInterface $httpClient;
    private RequestFactoryInterface $httpRequestFactory;
    private StreamFactoryInterface $httpStreamFactory;

    private string $sessionId;

    public function __construct(
        private readonly string $host = 'localhost',
        private readonly int $port = 8123,
        private readonly bool $https = false,
        private readonly string $user = 'default',
        private readonly string $password = '',
        private readonly ?string $database = 'default',

        /**
         * Set a custom PSR-18 HTTP client.
         * If not set, HTTPPlug Discovery will be used to find a client from composer dependencies.
         */
        ?ClientInterface $httpClient = null,

        /**
         * Set a custom PSR-17 request factory.
         * If not set, HTTPPlug Discovery will be used to find a factory from composer dependencies.
         */
        ?RequestFactoryInterface $httpRequestFactory = null,

        /**
         * Set a custom PSR-17 stream factory.
         * If not set, HTTPPlug Discovery will be used to find a factory from composer dependencies.
         */
        ?StreamFactoryInterface $httpStreamFactory = null
    )
    {

        $this->httpClient = $httpClient ?? Psr18ClientDiscovery::find();
        $this->httpRequestFactory = $httpRequestFactory ?? Psr17FactoryDiscovery::findRequestFactory();
        $this->httpStreamFactory = $httpStreamFactory ?? Psr17FactoryDiscovery::findStreamFactory();

        $this->sessionId = bin2hex(random_bytes(16));
    }

    public function ping() : bool
    {

        try {

            $request = $this->httpRequestFactory->createRequest('GET', $this->getUrl('/ping'));
            $response = $this->httpClient->sendRequest($request);

            if ($response->getStatusCode() !== 200) {
                return false;
            }

            $body = $response->getBody()->getContents();

            return $body === "Ok.\n";

        } catch (ClientExceptionInterface) {
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
     * @param array<string, scalar> ...$rows
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
     * @param array<string, scalar> $bindings
     */
    public function select(string $query, array $bindings = []) : ResultSet
    {
        $response = $this->query($query, $bindings);

        if (!is_array($response)) {
            // @codeCoverageIgnoreStart
            throw new ClickhouseHttpQueryException('Invalid query response. Expected array, got ' . gettype($response));
            // @codeCoverageIgnoreEnd
        }

        return new ResultSet($response);
    }

    /**
     * @param array<string, scalar> $bindings
     * @throws ClickhouseHttpQueryException
     */
    public function query(string $query, array $bindings = []) : mixed
    {

        $httpQuery = [
            // this does not work as POST params
            'session_id' => $this->sessionId,
        ];

        $url = $this->getUrl() . '/?' . http_build_query($httpQuery);

        $builder = new MultipartStreamBuilder($this->httpStreamFactory);
        $builder->addResource('query', $query);
        foreach ($bindings as $key => $value) {
            $builder->addResource('param_' . $key, (string) $value);
        }
        $boundary = $builder->getBoundary();
        $multipartStream = $builder->build();

        $request = $this->httpRequestFactory->createRequest('POST', $url)
            ->withBody($multipartStream)
            ->withHeader('Content-Type', 'multipart/form-data; boundary="'.$boundary.'"')
            ->withHeader('X-ClickHouse-Format', 'JSONCompact')
            ->withHeader('X-ClickHouse-User', $this->user)
            ->withHeader('X-ClickHouse-Key', $this->password);

        if ($this->database) {
            $request = $request->withHeader('X-ClickHouse-Database', $this->database);
        }

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $exception) {
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
        $scheme = $this->https ? 'https' : 'http';
        return $scheme . '://' . $this->host . ':' . $this->port . $append;
    }

}