<?php

namespace OpenOrchestra\FrontBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class OpenOrchestraFrontExtension extends Extension
{
    /**
     * Loads a specific configuration.
     *
     * @param array            $config   An array of configuration values
     * @param ContainerBuilder $container A ContainerBuilder instance
     *
     * @throws \InvalidArgumentException When provided tag is not defined in this extension
     *
     * @api
     */
    public function load(array $config, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $config);

        $container->setParameter('open_orchestra_front.template_set', $config['template_set']);
        $container->setParameter('open_orchestra_front.devices', $config['devices']);
        $container->setParameter('open_orchestra_front.device_type_field', $config['device_type_field']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('subscriber.yml');
        $loader->load('twig.yml');
        $loader->load('subquery.yml');
        $loader->load('database_routing.yml');

        $container->setAlias('templating', 'open_orchestra_front.twig.orchestra_twig_engine');
    }
}
