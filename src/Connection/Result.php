<?php

declare(strict_types=1);

namespace CrazyGoat\MysqlAsyncBundle\Connection;

use Amp\Future;
use Amp\Mysql\Internal\MysqlPooledResult;

class Result
{
    /**
     * @param Future<mixed> $future
     */
    public function __construct(private readonly Future $future)
    {
    }

    public function fetchScalar(): int|float|string|null
    {
        /** @var MysqlPooledResult $data */
        $data = $this->future->await();

        $row = $data->fetchRow();
        if (null === $row) {
            return null;
        }

        $key = array_key_first($row);

        if ($key !== null) {
            return $row[$key] ?? null;
        }

        return null;
    }
}
