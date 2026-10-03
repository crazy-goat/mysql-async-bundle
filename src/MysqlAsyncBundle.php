<?php

declare(strict_types=1);

namespace CrazyGoat\MysqlAsyncBundle;

use Amp\Sql\Common\SqlCommonConnectionPool;
use CrazyGoat\MysqlAsyncBundle\Connection\Pool;
use CrazyGoat\MysqlAsyncBundle\Connection\PoolFactory;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class MysqlAsyncBundle extends AbstractBundle
{
    protected string $extensionAlias = 'mysql_async';

    public function configure(DefinitionConfigurator $definition): void
    {
        $configurator = require __DIR__ . '/config/configuration.php';
        if (!is_callable($configurator)) {
            throw new \RuntimeException('The configuration parameter is not callable.');
        }

        $configurator($definition);
    }

    /**
     * @param array<string,mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        /** @var array<string,array<string,string>> $pools */
        $pools = $config['pool'] ?? [];

        foreach ($pools as $poolName => $poolOpts) {
            $url = $poolOpts['url'] ?? throw new \RuntimeException('The pool url is required.');

            $container
                ->register(sprintf('mysql_async.pool.%s', $poolName), Pool::class)
                ->setFactory([PoolFactory::class, 'create'])
                ->setArgument(0, $url)
                ->setArgument(1, SqlCommonConnectionPool::DEFAULT_MAX_CONNECTIONS)
                ->setArgument(2, SqlCommonConnectionPool::DEFAULT_IDLE_TIMEOUT)
                ->setPublic(false);
        }

        $container->setAlias(Pool::class, new Alias('mysql_async.pool.default', true));
    }
}
