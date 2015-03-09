<?php

namespace OpenOrchestra\FrontBundle\Controller;

use OpenOrchestra\FrontBundle\Exception\NonExistingNodeException;
use OpenOrchestra\ModelInterface\Model\NodeInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Config;

/**
 * Class NodeController
 */
class NodeController extends Controller
{
    /**
     * Render Node
     *
     * @param Request $request
     * @param int     $nodeId
     *
     * @Config\Route("/node/{nodeId}", name="open_orchestra_front_node")
     * @Config\Method({"GET"})
     *
     * @throws NonExistingNodeException
     * @return Response
     */
    public function showAction(Request $request, $nodeId)
    {
        /** @var NodeInterface $node */
        $node = $this->get('open_orchestra_model.repository.node')
            ->findOneByNodeIdAndLanguageWithPublishedAndLastVersionAndSiteId($nodeId, $request->getLocale());

        if (!($node instanceof NodeInterface)) {
            throw new NonExistingNodeException();
        } elseif (!is_null($node->getRole()) && !$this->get('security.context')->isGranted($node->getRole())) {
            return $this->redirect($this->get('request')->getBaseUrl());
        }

        $response = $this->renderNode($node);
        $this->get('fos_http_cache.cache_manager')->tagResponse($response, array('poc', 'node'));

        $response = $this->get('open_orchestra_display.manager.cacheable')->setMaxAge($node->getMaxAge(), $response);

        return $response;
    }

    /**
     * @param Request $request
     *
     * @Config\Route("/preview", name="open_orchestra_front_node_preview")
     * @Config\Method({"GET"})
     *
     * @return Response
     */
    public function previewAction(Request $request)
    {
        $token = $request->get('token');
        $decryptedToken = $this->get('open_orchestra_base.manager.encryption')->decrypt($token);
        $node = $this->get('open_orchestra_model.repository.node')->find($decryptedToken);
        $this->get('open_orchestra_display.manager.site')->setSiteId($node->getSiteId());

        return $this->renderNode($node);
    }

    /**
     * @param NodeInterface $node
     *
     * @return Response
     */
    protected function renderNode($node)
    {
        $response = $this->render(
            'OpenOrchestraFrontBundle:Node:show.html.twig',
            array(
                'node' => $node,
                'datetime' => time()
            )
        );

        return $response;
    }
}
