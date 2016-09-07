<?php

namespace OpenOrchestra\FrontBundle\DependencyInjection\Compiler;

use OpenOrchestra\BaseBundle\DependencyInjection\Compiler\AbstractTaggedCompiler;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class TwigTemplateLoaderCompilerPass
 */
class TwigTemplateLoaderCompilerPass extends AbstractTaggedCompiler implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $twigFilesystemLoaderDefinition = $container->getDefinition('twig.loader.filesystem');
        $twigFilesystemLoaderDefinition->addMethodCall('addPath', array(__DIR__.'/../../Resources/views/', 'Twig'));
    }
}
