<?php

namespace Okvpn\Bundle\FixtureBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OkvpnFixtureExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();

        $config = $this->processConfiguration($configuration, $configs);
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        if ($config['table'] !== null) {
            $container->setParameter('okvpn_fixture.table', $config['table']);
        }

        if ($config['path_main'] !== null) {
            $container->setParameter('okvpn_fixture.path_data_main', $config['path_main']);
        }

        if ($config['path_demo'] !== null) {
            $container->setParameter('okvpn_fixture.path_data_demo', $config['path_demo']);
        }
    }
}
