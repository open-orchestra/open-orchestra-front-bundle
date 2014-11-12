<?php

namespace PHPOrchestra\FrontBundle\Controller;

use PHPOrchestra\FrontBundle\Exception\NonExistingDocumentException;
use PHPOrchestra\ModelBundle\Model\NodeInterface;
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
     * @param int $nodeId
     *
     * @Config\Route("/node/{nodeId}", name="php_orchestra_front_node")
     * @Config\Method({"GET"})
     *
     * @throws NonExistingDocumentException
     * @return Response
     */
    public function showAction($nodeId)
    {
        $node = $this->get('php_orchestra_model.repository.node')
            ->findWithPublishedAndLastVersionAndSiteId($nodeId);

        if (is_null($node)) {
            throw new NonExistingDocumentException();
        } elseif (!is_null($node->getRole()) && !$this->get('security.context')->isGranted($node->getRole())) {
            return $this->redirect($this->get('request')->getBaseUrl());
        }

        return $this->renderNode($node);
    }

    /**
     * @param Request $request
     *
     * @Config\Route("/preview", name="php_orchestra_front_node_preview")
     * @Config\Method({"GET"})
     *
     * @return Response
     */
    public function previewAction(Request $request)
    {
        $token = $request->get('token');
        $decryptedToken = $this->get('php_orchestra_base.manager.encryption')->decrypt($token);
        $node = $this->get('php_orchestra_model.repository.node')->find($decryptedToken);

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
            'PHPOrchestraFrontBundle:Node:show.html.twig',
            array(
                'node' => $node,
                'datetime' => time()
            )
        );

        $response->setPublic();
        $response->setSharedMaxAge(100);
        $response->headers->addCacheControlDirective('must-revalidate', true);

        return $response;
    }
}
