<?php

declare(strict_types=1);

namespace CrazyGoat\MysqlAsyncBundle\Connection;

use function Amp\async;

use Amp\Mysql\MysqlConfig;
use Amp\Mysql\MysqlConnectionPool;
use Amp\Mysql\MysqlResult;
use Amp\Sql\Common\SqlCommonConnectionPool;
use http\Exception\InvalidArgumentException;

class Pool
{
    private ?MysqlConnectionPool $pool = null;

    public function __construct(
        private readonly MysqlConfig $config,
        private readonly int         $maxConnections = SqlCommonConnectionPool::DEFAULT_MAX_CONNECTIONS,
        private readonly int         $idleTimeout = SqlCommonConnectionPool::DEFAULT_IDLE_TIMEOUT,
    ) {
    }

    /**
     * @param array<string,scalar|null> $params
     */
    public function executeQuery(string $query, array $params = []): Result
    {
        $poll = $this->getPool();

        return new Result(async(fn(): MysqlResult => $poll->execute($query, $params)));
    }

    private function getPool(): MysqlConnectionPool
    {
        if ($this->pool instanceof MysqlConnectionPool) {
            return $this->pool;
        }

        if ($this->maxConnections <= 0) {
            throw new InvalidArgumentException('Maximum number of connections must be greater than 0');
        }

        if ($this->idleTimeout <= 0) {
            throw new InvalidArgumentException('Idle timeout must be greater than 0');
        }

        $this->pool = new MysqlConnectionPool($this->config, $this->maxConnections, $this->idleTimeout);

        return $this->pool;
    }
}
