<?php

declare(strict_types=1);

namespace CrazyGoat\MysqlAsyncBundle\Tests;

use CrazyGoat\MysqlAsyncBundle\Connection\Pool;
use CrazyGoat\MysqlAsyncBundle\MysqlAsyncBundle;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

/**
 * Boots the bundle in a real kernel: it loads `mysql_async`, compiles the container and hands
 * out the pool services. These tests need no database, because `Pool` only parses its
 * configuration and opens connections when a query runs.
 *
 * Every test uses its own `$id`, so that the cached container of one test is never reused for
 * another configuration.
 */
final class MysqlAsyncBundleTest extends TestCase
{
    private const URL = 'mysql://user:secret@db.example:3307/app';

    /** @var list<TestKernel> */
    private array $kernels = [];

    protected function tearDown(): void
    {
        foreach ($this->kernels as $kernel) {
            $kernel->shutdown();
        }

        $this->kernels = [];
    }

    public function testTheBundleIsRegisteredWithoutAnyConfiguration(): void
    {
        $container = $this->boot('no-config');

        $this->assertInstanceOf(Pool::class, $container->get(Pool::class));
    }

    public function testThePoolServiceIsAPool(): void
    {
        $container = $this->boot('pool-service', self::config());

        $this->assertInstanceOf(Pool::class, $container->get('mysql_async.pool.default'));
    }

    public function testThePoolAliasIsPublic(): void
    {
        $kernel = $this->bootKernel('public-alias', self::config());

        // The alias has to be public, otherwise autowiring Pool by type fails.
        $pool = $kernel->getContainer()->get(Pool::class);

        $this->assertInstanceOf(Pool::class, $pool);
    }

    public function testTheAliasAndTheNamedServiceAreTheSamePool(): void
    {
        $container = $this->boot('same-pool', self::config());

        $this->assertSame($container->get('mysql_async.pool.default'), $container->get(Pool::class));
    }

    /**
     * `CheckTypeDeclarationsPass` (used by `bin/console lint:container`) skips definitions that
     * have a factory, so no runtime check notices that the pool is declared as `PoolFactory`
     * while `PoolFactory::create()` returns a `Pool`.
     */
    public function testThePoolServiceIsDeclaredAsPool(): void
    {
        $container = new ContainerBuilder();

        (new MysqlAsyncBundle())->loadExtension(
            self::config(),
            $this->createStub(ContainerConfigurator::class),
            $container,
        );

        $definition = $container->getDefinition('mysql_async.pool.default');

        $this->assertSame(Pool::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());
    }

    /**
     * @param array<string,mixed> $config
     */
    #[DataProvider('invalidConfigurationProvider')]
    public function testInvalidConfigurationFailsToBoot(array $config, string $expectedMessage): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches($expectedMessage);

        $this->bootKernel('invalid', $config);
    }

    /**
     * @return iterable<string, array{array<string,mixed>, string}>
     */
    public static function invalidConfigurationProvider(): iterable
    {
        yield 'empty url' => [
            ['pool' => ['default' => ['url' => '']]],
            '/url.*cannot contain an empty value/i',
        ];

        yield 'unknown option' => [
            ['pool' => ['default' => ['unknown_option' => 'x']]],
            '/unknown_option/i',
        ];

        yield 'unknown pool name' => [
            ['pool' => ['other' => ['url' => 'mysql://user:secret@db.example/app']]],
            '/other/i',
        ];
    }

    /**
     * @param array<string,mixed> $config
     */
    private function boot(string $id, array $config = []): Container
    {
        $kernel = $this->bootKernel($id, $config);
        $testContainer = $kernel->getContainer()->get('test.service_container');

        self::assertInstanceOf(Container::class, $testContainer);

        return $testContainer;
    }

    /**
     * @param array<string,mixed> $config
     */
    private function bootKernel(string $id, array $config = []): TestKernel
    {
        $kernel = new TestKernel('test', true, $id, $config);
        $kernel->boot();
        $this->kernels[] = $kernel;

        return $kernel;
    }

    /**
     * @return array{pool: array{default: array{url: string}}}
     */
    private static function config(): array
    {
        return ['pool' => ['default' => ['url' => self::URL]]];
    }
}
