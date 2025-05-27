<?php

namespace CrazyGoat\MysqlAsyncBundle\Connection;

use Amp\Mysql\MysqlConfig;
use Amp\Mysql\MysqlConnectionPool;
use function Amp\async;

class Pool
{
    private ?MysqlConnectionPool $pool = null;

    public function __construct(
        private readonly MysqlConfig $config,
        private readonly int         $maxConnections = MysqlConnectionPool::DEFAULT_MAX_CONNECTIONS,
        private readonly int         $idleTimeout = MysqlConnectionPool::DEFAULT_IDLE_TIMEOUT
    )
    {
    }

    public function executeQuery(string $query, array $params = []): Result
    {
        $poll = $this->getPool();

        return new Result(async(fn() => $poll->execute($query, $params)));
    }

    private function getPool(): MysqlConnectionPool
    {
        if ($this->pool instanceof MysqlConnectionPool) {
            return $this->pool;
        }

        $this->pool = new MysqlConnectionPool($this->config, $this->maxConnections, $this->idleTimeout);

        return $this->pool;
    }
}