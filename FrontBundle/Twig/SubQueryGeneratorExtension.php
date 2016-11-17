<?php

namespace OpenOrchestra\FrontBundle\Twig;

use OpenOrchestra\FrontBundle\SubQuery\SubQueryGeneratorManager;

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
     * @param array   $blockParameters
     * @param boolean $blockPrivate
     * @param array   $baseSubQuery
     *
     * @return array
     */
    public function generateSubQuery(array $blockParameters, $blockPrivate, array $baseSubQuery)
    {
        return $this->subQueryGeneratorManager->generate($blockParameters, $blockPrivate, $baseSubQuery);
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
