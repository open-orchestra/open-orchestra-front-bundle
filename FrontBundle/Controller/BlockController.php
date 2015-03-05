<?php

namespace OpenOrchestra\FrontBundle\Controller;

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
        if ($token = $request->get('token')) {
            $decryptedToken = $this->get('open_orchestra_base.manager.encryption')->decrypt($token);
            $node = $this->get('open_orchestra_model.repository.node')->find($decryptedToken);
            $newNodeId = $node->getNodeId();
        }
        if (is_null($newNodeId) || $newNodeId != $nodeId) {
            $node = $this->get('open_orchestra_model.repository.node')
                ->findOneByNodeIdAndLanguageWithPublishedAndLastVersionAndSiteId($nodeId, $language, $siteId);
        }

        if ($node && (null !== ($block = $node->getBlock($blockId)))) {
            return $this->get('open_orchestra_display.display_block_manager')
                ->show($block);
        }

        throw new NotFoundHttpException();
    }
}
