<?php

namespace OpenOrchestra\FrontBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class TemplateCompilerPass
 */
class TemplateCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $templateManager = $container->getDefinition('open_orchestra_front.manager.template');
        $templateSet = $container->getParameter('open_orchestra_front.template_set');

        $templateManager->addMethodCall('setTemplateSet', array($templateSet));
    }
}
