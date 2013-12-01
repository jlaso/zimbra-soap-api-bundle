<?php

namespace JLaso\ZimbraSoapApiBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('jlaso_zimbra_soap_api');

        $rootNode
            ->children()
                ->scalarNode('server')
                    ->isRequired()
                ->end()
                ->scalarNode('port')
                    ->isRequired()
                ->end()
                ->scalarNode('user')
                    ->isRequired()
                ->end()
                ->scalarNode('password')
                    ->isRequired()
                ->end()
                ->scalarNode('debug')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

}

