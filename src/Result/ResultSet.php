<?php

namespace Hyvor\Clickhouse\Result;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

class ResultSet implements IteratorAggregate
{

    /**
     * @var array<mixed>
     */
    private array $result;

    public int $rows;

    public ?int $rowsBeforeLimitAtLeast;

    public float $elapsedTimeSeconds;

    public int $rowsRead;

    public int $bytesRead;

    /**
     * @param array<mixed> $json
     */
    public function __construct(array $json)
    {

        $columns = $json['meta'];

        foreach ($json['data'] as $rowJson) {
            $row = [];
            foreach ($rowJson as $key => $value) {
                $row[$columns[$key]['name']] = $value;
            }
            $this->result[] = $row;
        }

        $this->rows = $json['rows'];
        $this->rowsBeforeLimitAtLeast = $json['rows_before_limit_at_least'] ?? null;

        $this->elapsedTimeSeconds = $json['statistics']['elapsed'] ?? null;
        $this->rowsRead = $json['statistics']['rows_read'] ?? null;
        $this->bytesRead = $json['statistics']['bytes_read'] ?? null;

    }

    /**
     * @return array<mixed>
     */
    public function all() : array
    {
        return $this->result;
    }

    /**
     * @return array<mixed> | null
     */
    public function first() : ?array
    {
        return $this->result[0] ?? null;
    }

    public function value() : mixed
    {
        $row = $this->first();

        if (!$row) {
            return null;
        }

        return array_values($row)[0];
    }

    public function getIterator() : ArrayIterator
    {
        return new ArrayIterator($this->rows);
    }
}