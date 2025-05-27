<?php

declare(strict_types=1);

namespace CrazyGoat\MysqlAsyncBundle\Connection;

use Amp\Mysql\MysqlConfig;
use Amp\Sql\Common\SqlCommonConnectionPool;

class PoolFactory
{
    public static function create(
        string $url,
        int    $maxConnections = SqlCommonConnectionPool::DEFAULT_MAX_CONNECTIONS,
        int    $idleTimeout = SqlCommonConnectionPool::DEFAULT_IDLE_TIMEOUT,
    ): Pool {
        $parsedUrl = parse_url($url);
        $database = explode('/', trim($parsedUrl['path'] ?? '/', '/'))[0];
        $options = [];

        parse_str($parsedUrl['query'] ?? '', $options);

        $charset = $options['charset'] ?? MysqlConfig::DEFAULT_CHARSET;
        $collate = $options['collate'] ?? MysqlConfig::DEFAULT_COLLATE;
        $sqlMode = $options['sqlMode'] ?? null;
        $key = $options['key'] ?? '';

        if (!is_string($charset)) {
            throw new \RuntimeException('Invalid charset value');
        }

        if (!is_string($collate)) {
            throw new \RuntimeException('Invalid charset value');
        }

        if (!is_string($sqlMode) && !is_null($sqlMode)) {
            throw new \RuntimeException('Invalid SQL mode');
        }

        if (!is_string($key)) {
            throw new \RuntimeException('Invalid charset value');
        }
        $config = new MysqlConfig(
            host: $parsedUrl['host'] ?? '127.0.0.1',
            port: intval($parsedUrl['port'] ?? MysqlConfig::DEFAULT_PORT),
            user: $parsedUrl['user'] ?? null,
            password: $parsedUrl['pass'] ?? null,
            database: $database,
            charset: $charset,
            collate: $collate,
            sqlMode: $sqlMode,
            useCompression: boolval($options['useCompression'] ?? false),
            key: $key,
            useLocalInfile: boolval($options['useLocalInfile'] ?? false),
        );

        return new Pool(
            $config,
            $maxConnections,
            $idleTimeout,
        );
    }
}
