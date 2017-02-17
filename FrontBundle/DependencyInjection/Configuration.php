<?php

namespace OpenOrchestra\FrontBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('open_orchestra_front');

        $rootNode->children()
            ->arrayNode('devices')
                ->useAttributeAsKey('name')
                ->prototype('array')
                    ->children()
                        ->scalarNode('parent')->defaultNull()->end()
                    ->end()
                ->end()
            ->end()
            ->append($this->addTemplateSetConfiguration())
            ->scalarNode('device_type_field')->defaultValue('x-ua-device')->end()
            ->enumNode('routing_type')->values(array('file', 'database'))->defaultValue('database')->end()
        ->end();

        return $treeBuilder;
    }

    /**
     * @return \Symfony\Component\Config\Definition\Builder\NodeDefinition
     */
    public function addTemplateSetConfiguration()
    {
        $builder = new TreeBuilder();
        $templateSet = $builder->root('template_set');

        $templateSet
            ->info('Array of template set to describe a template. Used to render a node')
            ->useAttributeAsKey('name')
            ->prototype('array')
                ->children()
                    ->arrayNode('templates')
                        ->useAttributeAsKey('name')
                        ->prototype('scalar')->end()
                    ->end()
                ->end()
            ->end();

        $templateSet->defaultValue(array(
            'default'=>array(
                'templates' => array(
                    'default' => 'OpenOrchestraFrontBundle:Template/Default:default.html.twig',
                    'home' => 'OpenOrchestraFrontBundle:Template/Default:home.html.twig',
                    'column_left' => 'OpenOrchestraFrontBundle:Template/Default:column_left.html.twig',
                    'column_right' => 'OpenOrchestraFrontBundle:Template/Default:column_right.html.twig'
                )
            ),
        ));

        return $templateSet;
    }
}
