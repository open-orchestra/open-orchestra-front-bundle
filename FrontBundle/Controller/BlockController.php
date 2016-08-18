<?php

namespace OpenOrchestra\FrontBundle\Controller;

use OpenOrchestra\FrontBundle\Exception\NonExistingBlockException;
use OpenOrchestra\FrontBundle\Exception\NonExistingNodeException;
use OpenOrchestra\ModelInterface\Model\ReadBlockInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
     * @throws NotFoundHttpException
     * @return Response
     */
    public function showAction(Request $request, $siteId, $nodeId, $blockId)
    {
        try {
            $block = $this->get('open_orchestra_front.repository.block')->findBlock(
                $blockId,
                $nodeId,
                $request->getLocale(),
                $siteId,
                $request->get('token')
            );
            $hasEsi = $this->has('esi') && $this->get('esi')->hasSurrogateCapability($request);

            $response = $this->get('open_orchestra_display.display_block_manager')->show($block, $hasEsi);

            $this->tagResponse($response, $block, $nodeId, $siteId, $request->getLocale());

            return $response;
        } catch (NonExistingBlockException $e) {
            throw new NotFoundHttpException(null, $e);
        } catch (NonExistingNodeException $e) {
            throw new NotFoundHttpException(null, $e);
        }
    }

    /**
     * Tag response
     *
     * @param Response           $response
     * @param ReadBlockInterface $block
     * @param string             $nodeId
     * @param string             $siteId
     * @param string             $language
     */
    protected function tagResponse(Response $response, ReadBlockInterface $block, $nodeId, $siteId, $language)
    {
        $tagManager = $this->get('open_orchestra_base.manager.tag');

        $cacheTags = $this->get('open_orchestra_display.display_block_manager')->getCacheTags($block);

        $nodes = $this->getNodesUsingBlock($block, $nodeId);
        if (is_array($nodes)) {
            foreach($nodes as $node) {
                $cacheTags[] = $tagManager->formatNodeIdTag($node);
            }
        }

        $cacheTags[] = $tagManager->formatSiteIdTag($siteId);
        $cacheTags[] = $tagManager->formatLanguageTag($language);

        $this->get('open_orchestra_display.manager.cacheable')->addCacheTags($cacheTags);
    }

    /**
     * Get a list of nodes using $block
     *
     * @param ReadBlockInterface $block
     * @param string             $nodeId
     *
     * @return array
     */
    protected function getNodesUsingBlock(ReadBlockInterface $block, $nodeId)
    {
        $nodes = array();
        $areas = $block->getAreas();

        if (is_array($areas)) {
            foreach($areas as $area) {
                if (isset($area['nodeId']) && 0 === $area['nodeId']) {
                    if (!in_array($nodeId, $nodes)) {
                        $nodes[] = $nodeId;
                    }
                }
            }
        }

        return $nodes;
    }
}
