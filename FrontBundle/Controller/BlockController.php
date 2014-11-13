<?php

namespace PHPOrchestra\FrontBundle\Controller;

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
     * @param string  $nodeId
     * @param string  $blockId
     *
     * @Config\Route("/block/{nodeId}/{blockId}", name="php_orchestra_front_block")
     * @Config\Method({"GET"})
     *
     * @throws NotFoundHttpException
     * @return Response
     */
    public function showAction(Request $request, $nodeId, $blockId)
    {
        if ($token = $request->get('token')) {
            $decryptedToken = $this->get('php_orchestra_base.manager.encryption')->decrypt($token);
            $node = $this->get('php_orchestra_model.repository.node')->find($decryptedToken);
        } else {
            $node = $this->get('php_orchestra_model.repository.node')
                ->findOneByNodeIdAndLanguageWithPublishedAndLastVersionAndSiteId($nodeId, $request->getLocale());
        }

        if (null !== ($block = $node->getBlocks()->get($blockId))) {
            return $this->get('php_orchestra_display.display_block_manager')
                ->show($node->getBlocks()->get($blockId));
        }

        throw new NotFoundHttpException();
    }
}
