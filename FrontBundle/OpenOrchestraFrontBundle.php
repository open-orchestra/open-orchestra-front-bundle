<?php

namespace OpenOrchestra\FrontBundle;

use OpenOrchestra\FrontBundle\DependencyInjection\Compiler\SubQueryCompilerPass;
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
    }
}
