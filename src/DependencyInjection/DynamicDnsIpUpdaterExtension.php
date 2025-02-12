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
     * @throws \RuntimeException If DNS_DOMAINS environment variable is not set
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        // Load services configuration from YAML file
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../../config')
        );
        $loader->load('services.yaml');

        // Ensure DNS_DOMAINS environment variable is available
        if (!isset($_ENV['DNS_DOMAINS'])) {
            throw new \RuntimeException('The DNS_DOMAINS environment variable is not set. Please configure it in your .env file.');
        }
    }
}
