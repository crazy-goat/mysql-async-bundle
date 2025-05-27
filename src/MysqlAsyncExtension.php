<?php

namespace CrazyGoat\MysqlAsyncBundle;

use Amp\Sql\Common\SqlCommonConnectionPool;
use CrazyGoat\MysqlAsyncBundle\Connection\Pool;
use CrazyGoat\MysqlAsyncBundle\Connection\PoolFactory;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class MysqlAsyncExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $configs[0] ?? [];

        foreach ($config['pool'] as $poolName => $poolOpts) {
            $serviceId = sprintf('mysql_async.pool.%s', $poolName);

            $container
                ->register($serviceId, PoolFactory::class)
                ->setFactory([PoolFactory::class, 'create'])
                ->setArgument(0, $poolOpts['url'])
                ->setArgument(1, SqlCommonConnectionPool::DEFAULT_MAX_CONNECTIONS)
                ->setArgument(2, SqlCommonConnectionPool::DEFAULT_IDLE_TIMEOUT)
                ->setPublic(false);
        }

        $container->setAlias(Pool::class, new Alias('mysql_async.pool.default', true));
    }
}
