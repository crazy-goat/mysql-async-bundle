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
            throw new \RuntimeException('Invalid collate value');
        }

        if (!is_string($sqlMode) && !is_null($sqlMode)) {
            throw new \RuntimeException('Invalid SQL mode');
        }

        if (!is_string($key)) {
            throw new \RuntimeException('Invalid key value');
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
            useCompression: self::parseBoolOption('useCompression', $options['useCompression'] ?? null),
            key: $key,
            useLocalInfile: self::parseBoolOption('useLocalInfile', $options['useLocalInfile'] ?? null),
        );

        return new Pool(
            $config,
            $maxConnections,
            $idleTimeout,
        );
    }

    /**
     * Reads a boolean flag from the query options. Unlike `boolval()`, the string "false" is
     * false. True: `1`, `true`, `on`, `yes`; false: `0`, `false`, `off`, `no`, empty string.
     * Anything else is rejected, like the other options. Surrounding whitespace is trimmed
     * first, so a value that arrives with it keeps working: `parse_str` decodes `%20`, `%09`
     * and `%0D`, and turns `+` into a space, so a flag copied out of a url bar or out of an
     * `.env` file keeps its padding. A value of nothing but whitespace is an empty value, that
     * is false. Note amphp's own connection string parser enables the flag only for the literal
     * value `on`; this bundle also accepts the common `1`/`true` spellings, which amphp would
     * treat as off.
     */
    private static function parseBoolOption(string $name, mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (!is_string($value)) {
            throw new \RuntimeException("Invalid $name value");
        }

        return match (strtolower(trim($value))) {
            '1', 'true', 'on', 'yes' => true,
            '0', 'false', 'off', 'no', '' => false,
            default => throw new \RuntimeException("Invalid $name value"),
        };
    }
}
