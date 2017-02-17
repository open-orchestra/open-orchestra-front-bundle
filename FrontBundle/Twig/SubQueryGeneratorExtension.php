<?php

namespace OpenOrchestra\FrontBundle\Twig;

use OpenOrchestra\FrontBundle\SubQuery\SubQueryGeneratorManager;
use OpenOrchestra\ModelInterface\Model\ReadBlockInterface;

/**
 * Class SubQueryGeneratorExtension
 */
class SubQueryGeneratorExtension extends \Twig_Extension
{
    protected $subQueryGeneratorManager;

    /**
     * @param SubQueryGeneratorManager $subQueryGeneratorManager
     */
    public function __construct(SubQueryGeneratorManager $subQueryGeneratorManager)
    {
        $this->subQueryGeneratorManager = $subQueryGeneratorManager;
    }

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
        return $this->subQueryGeneratorManager->generate($block, $baseSubQuery);
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
