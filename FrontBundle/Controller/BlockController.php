<?php

namespace OpenOrchestra\FrontBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Config;
use OpenOrchestra\ModelInterface\Model\BlockInterface;
use OpenOrchestra\ModelInterface\Model\ReadNodeInterface;

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
     * @param string  $language
     *
     * @Config\Route("/block/{siteId}/{nodeId}/{blockId}/{language}", name="open_orchestra_front_block")
     * @Config\Method({"GET"})
     *
     * @throws NotFoundHttpException
     * @return Response
     */
    public function showAction(Request $request, $siteId, $nodeId, $blockId, $language)
    {
        $newNodeId = null;
        $node = null;

        if ($token = $request->get('token')) {
            $decryptedToken = $this->get('open_orchestra_base.manager.encryption')->decrypt($token);
            $node = $this->get('open_orchestra_model.repository.node')->find($decryptedToken);
            $newNodeId = $node->getNodeId();
        }

        if (is_null($newNodeId) || $newNodeId != $nodeId) {
            $node = $this->get('open_orchestra_model.repository.node')
                ->findOnePublishedByNodeIdAndLanguageAndSiteIdInLastVersion($nodeId, $language, $siteId);
        }

        if ($node instanceof ReadNodeInterface && (null !== ($block = $node->getBlock($blockId)))) {
            $response = $this->get('open_orchestra_display.display_block_manager')->show($block);

            $this->tagResponse($response, $block, $nodeId, $siteId, $language);

            return $response;
        }

        throw new NotFoundHttpException();
    }

    /**
     * Tag response
     * 
     * @param Response       $response
     * @param BlockInterface $block
     * @param string         $nodeId
     * @param string         $siteId
     * @param string         $language
     */
    protected function tagResponse(Response $response, BlockInterface $block, $nodeId, $siteId, $language)
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
     * @param BlockInterface $block
     * @param string         $nodeId
     * 
     * @return array
     */
    protected function getNodesUsingBlock(BlockInterface $block, $nodeId)
    {
        $nodes = array();
        $areas = $block->getAreas();

        if (is_array($areas)) {
            foreach($areas as $area) {
                if (isset($area['nodeId'])) {
                    $node = ($area['nodeId'] == 0) ? $nodeId : $area['nodeId'];
                    if (!in_array($node, $nodes)) {
                        $nodes[] = $node;
                    }
                }
            }
        }

        return $nodes;
    }
}
