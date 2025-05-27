<?php

namespace CrazyGoat\MysqlAsyncBundle\Connection;

use Amp\Future;
use Amp\Mysql\Internal\MysqlPooledResult;
use Symfony\Component\Stopwatch\StopwatchEvent;

class Result
{
    public function __construct(private readonly Future $future)
    {
    }

    public function fetchScalar(): int|float|string|null
    {
        $data = $this->future->await();

        if ($data instanceof MysqlPooledResult) {
            $row = $data->fetchRow();
            $key = array_key_first($row);

            if ($key !== null) {
                return $row[$key] ?? null;
            }
        }

        return null;
    }
}