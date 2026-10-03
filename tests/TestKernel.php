<?php

declare(strict_types=1);

namespace CrazyGoat\MysqlAsyncBundle\Tests;

use CrazyGoat\MysqlAsyncBundle\MysqlAsyncBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

/**
 * A minimal kernel that registers the bundle and nothing else.
 *
 * The container is cached in `var/test/<id>/`, so every test needs its own `$id`
 * whenever the bundle configuration differs.
 */
final class TestKernel extends Kernel
{
    /**
     * @param array<string,mixed> $bundleConfig configuration for the `mysql_async` extension
     */
    public function __construct(
        string $environment,
        bool $debug,
        private readonly string $id,
        private readonly array $bundleConfig = [],
    ) {
        parent::__construct($environment, $debug);
    }

    public function registerBundles(): iterable
    {
        return [new FrameworkBundle(), new MysqlAsyncBundle()];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(function (ContainerBuilder $container): void {
            $container->loadFromExtension('framework', [
                'secret' => 'mysql-async-bundle',
                'test' => true,
                'http_method_override' => false,
                'php_errors' => ['log' => true],
            ]);
            $container->loadFromExtension('mysql_async', $this->bundleConfig);
        });
    }

    public function getProjectDir(): string
    {
        return \dirname(__DIR__);
    }

    public function getCacheDir(): string
    {
        return $this->getProjectDir() . '/var/test/' . $this->id . '/cache';
    }

    public function getLogDir(): string
    {
        return $this->getProjectDir() . '/var/test/' . $this->id . '/log';
    }
}
