<?php

namespace CrazyGoat\MysqlAsyncBundle\Connection;

use Amp\Mysql\MysqlConfig;
use Amp\Sql\Common\SqlCommonConnectionPool;

class PoolFactory
{
    public static function create(
        string $url,
        int    $maxConnections = SqlCommonConnectionPool::DEFAULT_MAX_CONNECTIONS,
        int    $idleTimeout = SqlCommonConnectionPool::DEFAULT_IDLE_TIMEOUT
    ): Pool
    {
        $parsedUrl = parse_url($url);
        $database = explode('/', trim($parsedUrl['path'] ?? '/', '/'))[0] ?? null;
        $options = [];

        parse_str($parsedUrl['query'] ?? '', $options);

        $config = new MysqlConfig(
            host: $parsedUrl['host'] ?? '127.0.0.1',
            port: intval($parsedUrl['port'] ?? MysqlConfig::DEFAULT_PORT),
            user: $parsedUrl['user'] ?? null,
            password: $parsedUrl['pass'] ?? null,
            database: $database,
            charset: $options['charset'] ?? MysqlConfig::DEFAULT_CHARSET,
            collate: $options['collate'] ?? MysqlConfig::DEFAULT_COLLATE,
            sqlMode: $options['sqlMode'] ?? null,
            useCompression: boolval($options['useCompression'] ?? false),
            key: $options['key'] ?? '',
            useLocalInfile: boolval($options['useLocalInfile'] ?? false)
        );

        return new Pool(
            $config,
            $maxConnections,
            $idleTimeout,
        );
    }
}