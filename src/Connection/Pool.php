<?php

declare(strict_types=1);

namespace CrazyGoat\MysqlAsyncBundle\Connection;

use function Amp\async;

use Amp\Mysql\MysqlConfig;
use Amp\Mysql\MysqlConnectionPool;
use Amp\Mysql\MysqlResult;
use Amp\Sql\Common\SqlCommonConnectionPool;

class Pool
{
    private ?MysqlConnectionPool $pool = null;

    /** @var positive-int */
    private readonly int $maxConnections;

    /** @var positive-int */
    private readonly int $idleTimeout;

    public function __construct(
        private readonly MysqlConfig $config,
        int                         $maxConnections = SqlCommonConnectionPool::DEFAULT_MAX_CONNECTIONS,
        int                         $idleTimeout = SqlCommonConnectionPool::DEFAULT_IDLE_TIMEOUT,
    ) {
        // Validated here and not on the first query, so that a bad configuration fails when
        // the service is created instead of at runtime. `requirePositiveInt()` is what lets
        // PHPStan know the two properties stay positive.
        $this->maxConnections = $this->requirePositiveInt($maxConnections, 'Maximum number of connections must be greater than 0');
        $this->idleTimeout = $this->requirePositiveInt($idleTimeout, 'Idle timeout must be greater than 0');
    }

    /**
     * @param array<string,scalar|null> $params
     */
    public function executeQuery(string $query, array $params = []): Result
    {
        $pool = $this->getPool();

        return new Result(async(fn(): MysqlResult => $pool->execute($query, $params)));
    }

    private function getPool(): MysqlConnectionPool
    {
        if ($this->pool instanceof MysqlConnectionPool) {
            return $this->pool;
        }

        $this->pool = new MysqlConnectionPool($this->config, $this->maxConnections, $this->idleTimeout);

        return $this->pool;
    }

    /**
     * @return positive-int
     */
    private function requirePositiveInt(int $value, string $message): int
    {
        if ($value <= 0) {
            throw new \InvalidArgumentException($message);
        }

        return $value;
    }
}
