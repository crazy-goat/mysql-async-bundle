<?php

namespace App;

use Amp\Mysql\MysqlConfig;
use Amp\Mysql\MysqlConnectionPool;
use Symfony\Component\Stopwatch\Stopwatch;
use function Amp\async;

class MysqlAsyncPoll
{
    private ?MysqlConnectionPool $pool = null;

    public function __construct(
        private readonly MysqlConfig $config,
        private readonly int         $maxConnections = MysqlConnectionPool::DEFAULT_MAX_CONNECTIONS,
        private readonly int         $idleTimeout = MysqlConnectionPool::DEFAULT_IDLE_TIMEOUT,
        private readonly ?Stopwatch  $stopwatch = null
    )
    {
    }

    public function executeQuery(string $query, array $params = []): MysqlAsyncResult
    {
        $poll = $this->getPool();

        $event = $this->stopwatch->start('async');
        return new MysqlAsyncResult(async(fn() => $poll->execute($query,$params)), $event);
    }

    private function getPool(): MysqlConnectionPool
    {
        if ($this->pool instanceof MysqlConnectionPool) {
            return $this->pool;
        }

        $this->stopwatch->start('MysqlAsyncPool connection');
        $this->pool = new MysqlConnectionPool($this->config, $this->maxConnections, $this->idleTimeout);
        $this->stopwatch->stop('MysqlAsyncPool connection');

        return $this->pool;
    }
}