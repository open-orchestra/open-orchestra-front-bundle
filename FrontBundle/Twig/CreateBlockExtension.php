<?php

namespace OpenOrchestra\FrontBundle\Twig;

use OpenOrchestra\BaseBundle\Context\CurrentSiteIdInterface;
use OpenOrchestra\DisplayBundle\DisplayBlock\DisplayBlockManager;
use OpenOrchestra\FrontBundle\Exception\NonExistingAreaException;
use OpenOrchestra\ModelInterface\Model\BlockInterface;

/**
 * Class CreateBlockExtension
 */
class CreateBlockExtension extends \Twig_Extension
{
    protected $blockClass;
    protected $displayBlockManager;
    protected $siteManager;

    /**
     * @param string                 $blockClass
     * @param DisplayBlockManager    $displayBlockManager
     * @param CurrentSiteIdInterface $siteManager
     */
    public function __construct(
        $blockClass,
        DisplayBlockManager $displayBlockManager,
        CurrentSiteIdInterface $siteManager
    ) {
        $this->blockClass = $blockClass;
        $this->displayBlockManager = $displayBlockManager;
        $this->siteManager = $siteManager;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction(
                'create_block',
                array($this, 'createBlock'),
                array('needs_environment' => true, 'is_safe' => array('html'))
            ),
        );
    }

    /**
     * @param \Twig_Environment $env
     * @param string            $component
     * @param array             $attributes
     *
     * @return string
     * @throws NonExistingAreaException
     */
    public function createBlock(\Twig_Environment $env, $component, array $attributes = array())
    {
        /** @var BlockInterface $block */
        $block = new $this->blockClass();
        $block->setComponent($component);
        $block->setTransverse(false);
        $block->setSiteId($this->siteManager->getCurrentSiteId());
        $block->setLanguage($this->siteManager->getCurrentSiteDefaultLanguage());
        $block->setAttributes($attributes);

        return $this->displayBlockManager->show($block)->getContent();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'create_block';
    }
}
