<?php

declare(strict_types=1);

namespace CrazyGoat\MysqlAsyncBundle\Tests\Connection;

use CrazyGoat\MysqlAsyncBundle\Connection\Pool;
use CrazyGoat\MysqlAsyncBundle\Connection\PoolFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PoolFactoryTest extends TestCase
{
    public function testCreatesPoolFromUrl(): void
    {
        $pool = PoolFactory::create('mysql://user:secret@db.example:3307/app?charset=utf8mb4');

        $this->assertInstanceOf(Pool::class, $pool);
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
