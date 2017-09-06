<?php

namespace Okvpn\Bundle\FixtureBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $treeBuilder->root('okvpn_fixture')
            ->children()
                ->scalarNode('table')->defaultNull()->end()
                ->scalarNode('path_main')->defaultNull()->end()
                ->scalarNode('path_demo')->defaultNull()->end()
            ->end();

        return $treeBuilder;
    }
}
