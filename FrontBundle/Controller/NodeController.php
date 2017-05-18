<?php

namespace OpenOrchestra\FrontBundle\Controller;

use OpenOrchestra\FrontBundle\Exception\NonExistingNodeException;
use OpenOrchestra\FrontBundle\Manager\NodeResponseManager;
use OpenOrchestra\FrontBundle\Security\ContributionActionInterface;
use OpenOrchestra\ModelInterface\Model\NodeInterface;
use OpenOrchestra\ModelInterface\Model\ReadNodeInterface;
use OpenOrchestra\ModelInterface\Model\ReadSiteInterface;
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
        $siteId = $this->get('open_orchestra_display.manager.context')->getCurrentSiteId();
        $site = $this->get('open_orchestra_model.repository.site')->findOneBySiteId($siteId);

        /** @var ReadNodeInterface $node */
        $node = $this->get('open_orchestra_model.repository.node')
            ->findOnePublished($nodeId, $request->getLocale(), $siteId);

        if (!($node instanceof ReadNodeInterface)) {
            throw new NonExistingNodeException();
        }

        $this->denyAccessUnlessGranted(ContributionActionInterface::READ, $node);

        $response = $this->renderNode($node, $site);

        return $this->updateNodeResponse($response, $node, $request);
    }

    /**
     * Update response headers
     *
     * @param Response          $response
     * @param ReadNodeInterface $node
     * @param Request           $request
     *
     * @return Response
     */
    protected function updateNodeResponse(Response $response, ReadNodeInterface $node, Request $request)
    {
        $tagManager = $this->get('open_orchestra_base.manager.tag');
        $cacheableManager = $this->get('open_orchestra_display.manager.cacheable');

        $cacheTags = array(
            $tagManager->formatNodeIdTag($node->getNodeId()),
            $tagManager->formatLanguageTag($node->getLanguage()),
            $tagManager->formatSiteIdTag($node->getSiteId())
        );
        $cacheableManager->addCacheTags($cacheTags);

        $cacheInfo = $this->get('open_orchestra_front.manager.node_response_manager')->getNodeCacheInfo($node);
        $privacy = ($cacheInfo[NodeResponseManager::IS_PUBLIC]) ? CacheableInterface::CACHE_PUBLIC : CacheableInterface::CACHE_PRIVATE;

        if ($this->has('esi') && $this->get('esi')->hasSurrogateCapability($request)) {
            $response = $cacheableManager->setResponseCacheParameters(
                $response,
                (CacheableInterface::CACHE_PUBLIC === $privacy) ? $node->getMaxAge() : 0,
                $privacy,
                true
            );
        } else {
            $response = $cacheableManager->setResponseCacheParameters(
                $response,
                (CacheableInterface::CACHE_PUBLIC === $privacy) ? $cacheInfo[NodeResponseManager::MAX_AGE] : 0,
                $privacy
            );
        }

        return $response;
    }

    /**
     * Render the node version given by encoded $token
     *
     * @param Request  $request
     * @param string   $token
     *
     * @return Response
     */
    public function previewAction(Request $request, $token)
    {
        $decryptedToken = $this->get('open_orchestra_base.manager.encryption')->decrypt($token);
        /** @var NodeInterface $node */
        $node = $this->get('open_orchestra_model.repository.node')->findVersionByDocumentId($decryptedToken);

        $siteManager = $this->get('open_orchestra_display.manager.context');
        $siteManager->setSiteId($node->getSiteId());
        $siteManager->setCurrentLanguage($node->getLanguage());
        $site = $this->get('open_orchestra_model.repository.site')->findOneBySiteId($node->getSiteId());
        $this->updatePreviewRequestParameters($request, $node);

        return $this->renderNode($node, $site, array('token' => $token));
    }

    /**
     * @param Request           $request
     * @param ReadNodeInterface $node
     */
    protected function updatePreviewRequestParameters(Request $request, ReadNodeInterface $node)
    {
        $routeParams = $request->get('_route_params', array());
        $routeParams = array_merge(array(
            'siteId' => $node->getSiteId(),
            'nodeId' => $node->getNodeId(),
            '_locale' => $node->getLanguage()
        ),
            $routeParams
        );
        $request->attributes->set('_route_params', $routeParams);
        $request->request->set('nodeId', $node->getNodeId());
        $request->request->set('siteId', $node->getSiteId());
        $request->request->set('_locale', $node->getLanguage());
    }

    /**
     * @param ReadNodeInterface $node
     * @param ReadSiteInterface $site
     * @param array             $parameters
     *
     * @return Response
     */
    protected function renderNode(ReadNodeInterface $node, ReadSiteInterface $site, array $parameters = array())
    {
        $response = $this->render(
            $this->getTemplate($node),
            array(
                'node' => $node,
                'site' => $site,
                'parameters' => $parameters,
            )
        );

        return $response;
    }

    /**
     * @param ReadNodeInterface $node
     *
     * @return string
     * @throws \OpenOrchestra\FrontBundle\Exception\NonExistingTemplateException
     */
    protected function getTemplate(ReadNodeInterface $node) {
        $site = $this->get('open_orchestra_model.repository.site')->findOneBySiteId($node->getSiteId());

        $template = $node->getTemplate();
        $templateSet = $site->getTemplateSet();

        $templateManager = $this->get('open_orchestra_front.manager.template');

        return $templateManager->getTemplate($template, $templateSet);
    }
}
