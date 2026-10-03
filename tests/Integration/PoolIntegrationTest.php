<?php

declare(strict_types=1);

namespace CrazyGoat\MysqlAsyncBundle\Tests\Integration;

use function Amp\async;

use Amp\Sql\SqlQueryError;
use CrazyGoat\MysqlAsyncBundle\Connection\Pool;
use CrazyGoat\MysqlAsyncBundle\Connection\PoolFactory;
use PHPUnit\Framework\TestCase;
use Revolt\EventLoop;

/**
 * Runs `executeQuery()` and `fetchScalar()` against a real server. Skipped unless
 * `MYSQL_TEST_URL` holds a connection url, so `composer test` still works without a database.
 * The CI job `integration` starts a MySQL service and sets that variable.
 */
final class PoolIntegrationTest extends TestCase
{
    private const URL_ENV = 'MYSQL_TEST_URL';

    private static ?Pool $pool = null;

    protected function setUp(): void
    {
        $url = getenv(self::URL_ENV);

        if (!is_string($url) || '' === trim($url)) {
            self::markTestSkipped(\sprintf('Set %s to a MySQL url to run these tests.', self::URL_ENV));
        }

        self::$pool ??= PoolFactory::create($url);
    }

    public function testFetchScalarReturnsTheFirstColumnOfTheFirstRow(): void
    {
        $this->assertSame(42, $this->fetchScalar('SELECT 42 AS answer'));
    }

    public function testFetchScalarReturnsNullForAnEmptyResult(): void
    {
        $this->assertNull($this->fetchScalar('SELECT 1 AS one WHERE 1 = 0'));
    }

    public function testFetchScalarRethrowsAQueryError(): void
    {
        $this->expectException(SqlQueryError::class);
        $this->expectExceptionMessageMatches('/doesn\'t exist/');

        $this->fetchScalar('SELECT * FROM this_table_does_not_exist');
    }

    public function testFetchScalarReturnsAString(): void
    {
        $this->assertSame('hello', $this->fetchScalar("SELECT 'hello' AS greeting"));
    }

    /**
     * The value type is not declared here: the future that `executeQuery()` returns is untyped
     * (see `Result`), and the assertions are what check the values.
     */
    private function fetchScalar(string $query): mixed
    {
        $pool = self::$pool;
        self::assertInstanceOf(Pool::class, $pool);

        // amphp needs a running event loop for the socket, and `EventLoop::run()` takes no
        // callback: it drives the loop until the fiber started by `async()` is done.
        $future = async(static fn(): mixed => $pool->executeQuery($query)->fetchScalar());
        EventLoop::run();

        return $future->await();
    }
}
