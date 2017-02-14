<?php

namespace OpenOrchestra\FrontBundle\Controller;

use OpenOrchestra\FrontBundle\Exception\DisplayBlockException;
use OpenOrchestra\ModelInterface\Model\ReadBlockInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Config;

/**
 * Class BlockController
 */
class BlockController extends Controller
{
    /**
     * Display the response linked to a block
     *
     * @param Request $request
     * @param string  $siteId
     * @param string  $nodeId
     * @param string  $blockId
     *
     * @Config\Route("/block/{siteId}/{nodeId}/{blockId}/{_locale}", name="open_orchestra_front_block")
     * @Config\Method({"GET", "POST"})
     *
     * @throws DisplayBlockException
     * @return Response
     */
    public function showAction(Request $request, $siteId, $nodeId, $blockId)
    {
        try {
            $block = $this->get('open_orchestra_model.repository.block')->findById($blockId);

            $response = $this->get('open_orchestra_display.display_block_manager')->show($block);
            $this->tagResponse($block, $nodeId, $siteId, $request->getLocale());

            return $response;
        } catch (\Exception $e) {
            throw new DisplayBlockException($e->getMessage(), $e->getCode(), $e);
        }
    }


    /**
     * Display a virtual block
     *
     * @param Request $request
     * @param string  $siteId
     * @param string  $nodeId
     * @param string  $blockId
     *
     * @Config\Route("/block/{siteId}/{nodeId}/{blockId}/{_locale}", name="open_orchestra_front_block")
     * @Config\Method({"GET", "POST"})
     *
     * @throws DisplayBlockException
     * @return Response
     */
    public function showVirtualAction(Request $request, $siteId, $nodeId, $blockId)
    {
        try {
            $block = $this->get('open_orchestra_model.repository.block')->findById($blockId);

            $response = $this->get('open_orchestra_display.display_block_manager')->show($block);
            $this->tagResponse($block, $nodeId, $siteId, $request->getLocale());

            return $response;
        } catch (\Exception $e) {
            throw new DisplayBlockException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Tag response
     *
     * @param ReadBlockInterface $block
     * @param string             $nodeId
     * @param string             $siteId
     * @param string             $language
     */
    protected function tagResponse(ReadBlockInterface $block, $nodeId, $siteId, $language)
    {
        $tagManager = $this->get('open_orchestra_base.manager.tag');

        $cacheTags = $this->get('open_orchestra_display.display_block_manager')->getCacheTags($block);

        if (true === $block->isTransverse()) {
            $cacheTags[] = $tagManager->formatNodeIdTag($nodeId);
        }

        $cacheTags[] = $tagManager->formatSiteIdTag($siteId);
        $cacheTags[] = $tagManager->formatLanguageTag($language);

        $this->get('open_orchestra_display.manager.cacheable')->addCacheTags($cacheTags);
    }
}
