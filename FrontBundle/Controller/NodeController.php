<?php

namespace OpenOrchestra\FrontBundle\Controller;

use OpenOrchestra\FrontBundle\Exception\NonExistingNodeException;
use OpenOrchestra\ModelInterface\Model\ReadNodeInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Config;
use OpenOrchestra\ModelInterface\Model\CacheableInterface;

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
        $siteId = $this->get('open_orchestra_display.manager.site')->getCurrentSiteId();
        /** @var ReadNodeInterface $node */
        $node = $this->get('open_orchestra_model.repository.node')
            ->findOnePublishedByNodeIdAndLanguageAndSiteIdInLastVersion($nodeId, $request->getLocale(), $siteId);

        if (!($node instanceof ReadNodeInterface)) {
            throw new NonExistingNodeException();
        } elseif (!is_null($node->getRole()) && !$this->get('security.authorization_checker')->isGranted($node->getRole())) {
            return $this->redirect($this->get('request')->getBaseUrl());
        }

        $parameters = $this->container->get('open_orchestra_front.manager.sub_query_parameters');

        $response = $this->renderNode($node, $parameters->generate($request, $node));

        return $this->updateNodeResponse($response, $node);
    }

    /**
     * Update response headers
     * 
     * @param Response      $response
     * @param ReadNodeInterface $node
     * 
     * @return Response
     */
    protected function updateNodeResponse(Response $response, ReadNodeInterface $node)
    {
        $tagManager = $this->get('open_orchestra_base.manager.tag');
        $cacheableManager = $this->get('open_orchestra_display.manager.cacheable');

        $cacheTags = array(
            $tagManager->formatNodeIdTag($node->getNodeId()),
            $tagManager->formatLanguageTag($node->getLanguage()),
            $tagManager->formatSiteIdTag($node->getSiteId())
        );
        $cacheableManager->tagResponse($response, $cacheTags);

        $response = $cacheableManager->setResponseCacheParameters(
            $response,
            $node->getMaxAge(),
            CacheableInterface::CACHE_PUBLIC
        );

        return $response;
    }

    /**
     * Render the node version given by encoded $token
     * 
     * @param string  $token
     * @param Request $request
     *
     * @return Response
     */
    public function previewAction($token, Request $request)
    {
        $decryptedToken = $this->get('open_orchestra_base.manager.encryption')->decrypt($token);
        $node = $this->get('open_orchestra_model.repository.node')->findOneById($decryptedToken);

        $parameters = $this->container->get('open_orchestra_front.manager.sub_query_parameters');

        return $this->renderNode($node, $parameters->generate($request, $node));
    }

    /**
     * @param ReadNodeInterface $node
     * @param array             $parameters
     *
     * @return Response
     */
    protected function renderNode($node, $parameters)
    {
        $response = $this->render(
            'OpenOrchestraFrontBundle:Node:show.html.twig',
            array(
                'node' => $node,
                'parameters' => $parameters
            )
        );

        return $response;
    }
}
