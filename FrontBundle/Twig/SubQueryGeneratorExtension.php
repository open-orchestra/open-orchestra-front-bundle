<?php

namespace OpenOrchestra\FrontBundle\Twig;

use OpenOrchestra\ModelInterface\Model\ReadBlockInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Class SubQueryGeneratorExtension
 */
class SubQueryGeneratorExtension extends \Twig_Extension implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @return array
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('generate_subquery', array($this, 'generateSubQuery')),
        );
    }

    /**
     * @param ReadBlockInterface $block
     * @param array              $baseSubQuery
     *
     * @return array
     */
    public function generateSubQuery(ReadBlockInterface $block, array $baseSubQuery)
    {
        $subQueryGeneratorManager = $this->container->get('open_orchestra_front.sub_query.manager');

        return $subQueryGeneratorManager->generate($block, $baseSubQuery);
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'sub_query';
    }
}
