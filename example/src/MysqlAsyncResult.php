<?php

namespace App;

use Amp\Future;
use Amp\Mysql\Internal\MysqlPooledResult;
use Symfony\Component\Stopwatch\StopwatchEvent;

class MysqlAsyncResult
{
    public function __construct(private readonly Future $future, private readonly ?StopwatchEvent $event)
    {
    }

    public function fetchScalar(): int|float|string|null
    {
        $data = $this->future->await();
        if ($this->event->isStarted()) {
            $this->event->stop();
        }
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