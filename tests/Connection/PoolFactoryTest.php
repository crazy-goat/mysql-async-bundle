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

        yield 'the flags spelled off' => [
            'mysql://db.example/app?useCompression=false&useLocalInfile=off',
            self::expected(),
        ];

        yield 'the flags spelled on' => [
            'mysql://db.example/app?useCompression=on&useLocalInfile=true',
            self::expected(['useCompression' => true, 'useLocalInfile' => true]),
        ];
    }

    /**
     * Pins the whole accepted set of both boolean flags. Every row also pins the flag that is
     * not in the query string, so a value can never leak from one option into the other.
     */
    #[DataProvider('booleanFlagProvider')]
    public function testParsesBooleanFlags(string $url, bool $useCompression, bool $useLocalInfile): void
    {
        $config = self::configOf(PoolFactory::create($url));

        self::assertSame($useCompression, $config->isCompressionEnabled(), 'useCompression');
        self::assertSame($useLocalInfile, $config->isLocalInfileEnabled(), 'useLocalInfile');
    }

    /**
     * @return iterable<string, array{string, bool, bool}>
     */
    public static function booleanFlagProvider(): iterable
    {
        $base = 'mysql://db.example/app';

        yield 'no options at all' => [$base, false, false];
        yield '1' => [$base . '?useCompression=1&useLocalInfile=1', true, true];
        yield 'true' => [$base . '?useCompression=true&useLocalInfile=true', true, true];
        yield 'on' => [$base . '?useCompression=on&useLocalInfile=on', true, true];
        yield 'yes' => [$base . '?useCompression=yes&useLocalInfile=yes', true, true];
        yield 'upper case' => [$base . '?useCompression=TRUE&useLocalInfile=ON', true, true];
        yield '0' => [$base . '?useCompression=0&useLocalInfile=0', false, false];
        yield 'false' => [$base . '?useCompression=false&useLocalInfile=false', false, false];
        yield 'off' => [$base . '?useCompression=off&useLocalInfile=off', false, false];
        yield 'no' => [$base . '?useCompression=no&useLocalInfile=no', false, false];
        yield 'empty' => [$base . '?useCompression=&useLocalInfile=', false, false];

        // `parse_str` decodes `%20`, `%09` and `%0D`, and turns `+` into a space, so a value
        // pasted from a url bar or copied out of a `.env` line arrives with surrounding
        // whitespace. A raw control character never gets that far: `parse_url` rewrites it to
        // an underscore, which is why the carriage return rows are percent-encoded.
        yield 'a leading space' => [$base . '?useCompression=%201', true, false];
        yield 'a trailing space' => [$base . '?useCompression=1%20', true, false];
        yield 'a plus sign, which parse_str reads as a space' => [$base . '?useCompression=+1', true, false];
        yield 'a trailing tab' => [$base . '?useLocalInfile=1%09', false, true];
        yield 'a trailing carriage return' => [$base . '?useCompression=1%0D', true, false];
        yield 'a carriage return on its own, an empty value' => [$base . '?useCompression=%0D', false, false];
        yield 'whitespace around a value' => [$base . '?useCompression=+yes%20&useLocalInfile=%09no', true, false];
    }

    #[DataProvider('invalidBooleanFlagProvider')]
    public function testRejectsInvalidBooleanFlag(string $query, string $expectedMessage): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        PoolFactory::create('mysql://user:secret@db.example/app?' . $query);
    }

    /**
     * Pins the rejected set of both boolean flags. `boolval()` used to enable the flag for every
     * one of these spellings.
     *
     * @return iterable<string, array{non-empty-string, non-empty-string}>
     */
    public static function invalidBooleanFlagProvider(): iterable
    {
        // parse_str turns `name[]=x` into an array, so the option is not a string.
        yield 'useCompression as an array' => ['useCompression[]=1', 'Invalid useCompression value'];
        yield 'useLocalInfile as an array' => ['useLocalInfile[]=1', 'Invalid useLocalInfile value'];
        yield 'y' => ['useCompression=y', 'Invalid useCompression value'];
        yield 't' => ['useLocalInfile=t', 'Invalid useLocalInfile value'];
        yield 'enabled' => ['useCompression=enabled', 'Invalid useCompression value'];
        yield '2' => ['useCompression=2', 'Invalid useCompression value'];
        yield '-1' => ['useLocalInfile=-1', 'Invalid useLocalInfile value'];
        yield '1.0' => ['useCompression=1.0', 'Invalid useCompression value'];
        yield 'surrounded by spaces but not a boolean' => ['useCompression=%20maybe%20', 'Invalid useCompression value'];
        yield 'maybe' => ['useLocalInfile=maybe', 'Invalid useLocalInfile value'];
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
