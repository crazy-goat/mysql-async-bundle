<?php

declare(strict_types=1);

namespace CrazyGoat\MysqlAsyncBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
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

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->registerExtension(new MysqlAsyncExtension());
    }
}
