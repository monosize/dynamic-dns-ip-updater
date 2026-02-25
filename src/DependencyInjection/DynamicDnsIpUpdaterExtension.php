<?php

declare(strict_types=1);

namespace Monosize\DynamicDnsIpUpdater\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Extension class for loading bundle configuration and services.
 */
class DynamicDnsIpUpdaterExtension extends Extension
{
    /**
     * Loads the bundle configuration and services.
     *
     * @param array            $configs   Configuration array from config files
     * @param ContainerBuilder $container The service container
     *
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        // Skip registration if DNS_DOMAINS is not configured
        if (!isset($_ENV['DNS_DOMAINS'])) {
            return;
        }

        // Load services configuration from YAML file
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../../config')
        );
        $loader->load('services.yaml');
    }
}
