<?php

declare(strict_types=1);

namespace CrazyGoat\MysqlAsyncBundle\Tests\Connection;

use CrazyGoat\MysqlAsyncBundle\Connection\Pool;
use CrazyGoat\MysqlAsyncBundle\Connection\PoolFactory;
use PHPUnit\Framework\TestCase;

final class PoolFactoryTest extends TestCase
{
    public function testCreatesPoolFromUrl(): void
    {
        $pool = PoolFactory::create('mysql://user:secret@db.example:3307/app?charset=utf8mb4');

        $this->assertInstanceOf(Pool::class, $pool);
    }

    public function testRejectsNonStringCharset(): void
    {
        $this->expectException(\RuntimeException::class);

        PoolFactory::create('mysql://user:secret@db.example/app?charset[]=utf8');
    }

    public function testRejectsInvalidMaxConnectionsOnFirstQuery(): void
    {
        $pool = PoolFactory::create('mysql://user:secret@db.example/app', 0);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Maximum number of connections must be greater than 0');

        $pool->executeQuery('SELECT 1');
    }

    public function testRejectsInvalidIdleTimeoutOnFirstQuery(): void
    {
        $pool = PoolFactory::create('mysql://user:secret@db.example/app', 1, 0);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Idle timeout must be greater than 0');

        $pool->executeQuery('SELECT 1');
    }
}
