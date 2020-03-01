<?php

namespace Tzunghaor\FormFlowBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('tzunghaor_form_flow');

        $rootNode
            ->children()
                ->arrayNode('flows')
                    ->info('Named form flow definitions: these will be wired with default services.')
                    ->example(['my-flow' => 'App\\Flows\\MyFlowDefinition'])
                    ->normalizeKeys(false)
                    ->useAttributeAsKey('name')
                    ->prototype('scalar')
                    ->end()
                ->end()
                ->scalarNode('storage')
                    ->info('Default storage service (must implement StorageInterface)')
                    ->defaultValue('tzunghaor_form_flow.utils.session_storage')
                ->end()
                ->booleanNode('redirect_already_finished')
                    ->info('Redirects to the flow\'s finished route if user tries to navigate into an already finished flow.')
                    ->defaultTrue()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
