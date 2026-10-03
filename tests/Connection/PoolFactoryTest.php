<?php

declare(strict_types=1);

namespace CrazyGoat\MysqlAsyncBundle\Tests\Connection;

use Amp\Mysql\MysqlConfig;
use CrazyGoat\MysqlAsyncBundle\Connection\Pool;
use CrazyGoat\MysqlAsyncBundle\Connection\PoolFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PoolFactoryTest extends TestCase
{
    /**
     * @param array<string,bool|int|string|null> $expected
     */
    #[DataProvider('parsedUrlProvider')]
    public function testParsesTheUrlIntoMysqlConfig(string $url, array $expected): void
    {
        $config = self::configOf(PoolFactory::create($url));

        self::assertSame($expected['host'], $config->getHost(), 'host');
        self::assertSame($expected['port'], $config->getPort(), 'port');
        self::assertSame($expected['user'], $config->getUser(), 'user');
        self::assertSame($expected['password'], $config->getPassword(), 'password');
        self::assertSame($expected['database'], $config->getDatabase(), 'database');
        self::assertSame($expected['charset'], $config->getCharset(), 'charset');
        self::assertSame($expected['collation'], $config->getCollation(), 'collation');
        self::assertSame($expected['sqlMode'], $config->getSqlMode(), 'sqlMode');
        self::assertSame($expected['key'], $config->getKey(), 'key');
        self::assertSame($expected['useCompression'], $config->isCompressionEnabled(), 'useCompression');
        self::assertSame($expected['useLocalInfile'], $config->isLocalInfileEnabled(), 'useLocalInfile');
    }

    /**
     * @return iterable<string, array{string, array<string, bool|int|string|null>}>
     */
    public static function parsedUrlProvider(): iterable
    {
        yield 'no options at all' => ['mysql://db.example/app', self::expected()];

        yield 'user, password and port' => [
            'mysql://user:secret@db.example:3307/app',
            self::expected(['port' => 3307, 'user' => 'user', 'password' => 'secret']),
        ];

        yield 'every option' => [
            'mysql://user:secret@db.example:3307/app'
                . '?charset=latin1&collate=latin1_general_ci&sqlMode=STRICT_ALL_TABLES&key=abc'
                . '&useCompression=1&useLocalInfile=1',
            self::expected([
                'port' => 3307,
                'user' => 'user',
                'password' => 'secret',
                'charset' => 'latin1',
                'collation' => 'latin1_general_ci',
                'sqlMode' => 'STRICT_ALL_TABLES',
                'key' => 'abc',
                'useCompression' => true,
                'useLocalInfile' => true,
            ]),
        ];

        yield 'no host, so the default one is used' => [
            'mysql:/app',
            self::expected(['host' => '127.0.0.1']),
        ];

        yield 'the database is the first path segment' => [
            'mysql://db.example/app/ignored',
            self::expected(),
        ];

        yield 'the flags are off' => [
            'mysql://db.example/app?useCompression=0&useLocalInfile=0',
            self::expected(),
        ];
    }

    /**
     * The values a url without options is expected to produce.
     *
     * @param array<string, bool|int|string|null> $overrides
     *
     * @return array<string, bool|int|string|null>
     */
    private static function expected(array $overrides = []): array
    {
        return array_merge([
            'host' => 'db.example',
            'port' => MysqlConfig::DEFAULT_PORT,
            'user' => null,
            'password' => null,
            'database' => 'app',
            'charset' => MysqlConfig::DEFAULT_CHARSET,
            'collation' => MysqlConfig::DEFAULT_COLLATE,
            'sqlMode' => null,
            'key' => '',
            'useCompression' => false,
            'useLocalInfile' => false,
        ], $overrides);
    }

    /**
     * `Pool` keeps its configuration private and the bundle exposes no getter for it, so the
     * test reads it the only way left.
     */
    private static function configOf(Pool $pool): MysqlConfig
    {
        $config = (new \ReflectionProperty($pool, 'config'))->getValue($pool);

        self::assertInstanceOf(MysqlConfig::class, $config);

        return $config;
    }

    /**
     * @param non-empty-string $query
     */
    #[DataProvider('invalidOptionProvider')]
    public function testRejectsInvalidOption(string $query, string $expectedMessage): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        PoolFactory::create('mysql://user:secret@db.example/app?' . $query);
    }

    /**
     * @return iterable<string, array{non-empty-string, non-empty-string}>
     */
    public static function invalidOptionProvider(): iterable
    {
        // parse_str turns `name[]=x` into an array, so the option is not a string.
        yield 'charset' => ['charset[]=utf8', 'Invalid charset value'];
        yield 'collate' => ['collate[]=utf8mb4_general_ci', 'Invalid collate value'];
        yield 'sqlMode' => ['sqlMode[]=STRICT_ALL_TABLES', 'Invalid SQL mode'];
        yield 'key' => ['key[]=secret', 'Invalid key value'];
    }

    public function testRejectsInvalidMaxConnections(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Maximum number of connections must be greater than 0');

        PoolFactory::create('mysql://user:secret@db.example/app', 0);
    }

    public function testRejectsInvalidIdleTimeout(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Idle timeout must be greater than 0');

        PoolFactory::create('mysql://user:secret@db.example/app', 1, 0);
    }
}
