<?php

namespace OpenOrchestra\FrontBundle\DependencyInjection\Compiler;

use OpenOrchestra\BaseBundle\DependencyInjection\Compiler\AbstractTaggedCompiler;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class SubQueryCompilerPass
 */
class SubQueryCompilerPass extends AbstractTaggedCompiler implements CompilerPassInterface
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     *
     * @api
     */
    public function process(ContainerBuilder $container)
    {
        $managerName = 'open_orchestra_front.sub_query.manager';
        $tagName = 'open_orchestra_front.sub_query.strategy';

        $this->addStrategyToManager($container, $managerName, $tagName);
    }
}
