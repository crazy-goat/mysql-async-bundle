<?php

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
        $configurator($definition);
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->registerExtension(new MysqlAsyncExtension());
    }
}