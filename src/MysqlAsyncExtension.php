<?php

declare(strict_types=1);

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

        if (!is_array($config['pool'])) {
            return;
        }

        foreach ($config['pool'] as $poolName => $poolOpts) {
            $serviceId = sprintf('mysql_async.pool.%s', $poolName);
            if (!is_array($poolOpts)) {
                throw new \RuntimeException('Pool options must be an array');
            }
            $url = is_string($poolOpts['url'] ?? null) ? $poolOpts['url'] : throw new \RuntimeException('pool url not set');

            $container
                ->register($serviceId, PoolFactory::class)
                ->setFactory(sprintf('%s::%s', PoolFactory::class, 'create'))
                ->setArgument(0, $url)
                ->setArgument(1, SqlCommonConnectionPool::DEFAULT_MAX_CONNECTIONS)
                ->setArgument(2, SqlCommonConnectionPool::DEFAULT_IDLE_TIMEOUT)
                ->setPublic(false);
        }

        $container->setAlias(Pool::class, new Alias('mysql_async.pool.default', true));
    }
}
