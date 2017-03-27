<?php

namespace OpenOrchestra\FrontBundle\Twig;

use OpenOrchestra\FrontBundle\Exception\NonExistingAreaException;
use OpenOrchestra\ModelInterface\Model\BlockInterface;
use OpenOrchestra\ModelInterface\Model\ReadBlockInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Class BlockExtension
 */
class BlockExtension extends \Twig_Extension implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @return array
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction(
                'create_block',
                array($this, 'createBlock'),
                array('is_safe' => array('html'))
            ),
            new \Twig_SimpleFunction(
                'render_shared_block',
                array($this, 'renderSharedBlock'),
                array('is_safe' => array('html'))
            ),
        );
    }

    /**
     * @param string            $component
     * @param array             $attributes
     *
     * @return string
     * @throws NonExistingAreaException
     */
    public function createBlock($component, array $attributes = array())
    {
        $siteManager = $this->container->get('open_orchestra_display.manager.site');
        $displayBlockManager = $this->container->get('open_orchestra_display.display_block_manager');
        $blockClass = $this->container->getParameter('open_orchestra_model.document.block.class');

        /** @var BlockInterface $block */
        $block = new $blockClass;
        $block->setComponent($component);
        $block->setId(uniqid(BlockInterface::TEMP_ID_PREFIX));
        $block->setTransverse(false);
        $block->setSiteId($siteManager->getCurrentSiteId());
        $block->setLanguage($siteManager->getCurrentSiteDefaultLanguage());
        $block->setAttributes($attributes);

        return $displayBlockManager->show($block)->getContent();
    }

    /**
     * @param string $code
     * @param string $language
     * @return string
     *
     * @throws \OpenOrchestra\DisplayBundle\Exception\DisplayBlockStrategyNotFoundException
     */
    public function renderSharedBlock($code, $language)
    {
        $blockRepository = $this->container->get('open_orchestra_model.repository.block');
        $block = $blockRepository->findTransverseBlockByCodeAndLanguage($code, $language);

        if ($block instanceof ReadBlockInterface) {
            $displayBlockManager = $this->container->get('open_orchestra_display.display_block_manager');

            return $displayBlockManager->show($block)->getContent();
        }

        return '';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'oo_block';
    }
}
