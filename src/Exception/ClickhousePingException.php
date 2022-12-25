<?php

namespace Hyvor\Clickhouse\Exception;

class ClickhousePingException extends ClickhouseException
{
    public function __construct(string $message = 'Clickhouse ping failed', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}