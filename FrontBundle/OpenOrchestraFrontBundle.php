<?php

namespace OpenOrchestra\FrontBundle;

use OpenOrchestra\FrontBundle\DependencyInjection\Compiler\SubQueryCompilerPass;
use OpenOrchestra\FrontBundle\DependencyInjection\Compiler\TwigTemplateLoaderCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class OpenOrchestraFrontBundle
 */
class OpenOrchestraFrontBundle extends Bundle
{
    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new SubQueryCompilerPass());
        $container->addCompilerPass(new TwigTemplateLoaderCompilerPass());
    }
}
