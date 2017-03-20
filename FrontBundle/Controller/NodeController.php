<?php

namespace OpenOrchestra\FrontBundle\Controller;

use OpenOrchestra\FrontBundle\Exception\NonExistingNodeException;
use OpenOrchestra\FrontBundle\Security\ContributionActionInterface;
use OpenOrchestra\ModelInterface\Model\NodeInterface;
use OpenOrchestra\ModelInterface\Model\ReadNodeInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Config;
use OpenOrchestra\ModelInterface\Model\CacheableInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

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
            ->findOnePublished($nodeId, $request->getLocale(), $siteId);

        if (!($node instanceof ReadNodeInterface)) {
            throw new NonExistingNodeException();
        }

        if (!empty($node->getFrontRoles())) {
            if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
                $this->denyAccessUnlessGranted(ContributionActionInterface::READ, $node);
            } else {
                throw new AuthenticationException();

            }
        }

        $response = $this->renderNode($node);

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

        if ($this->has('esi') && $this->get('esi')->hasSurrogateCapability($request)) {
            $response = $cacheableManager->setResponseCacheParameters(
                $response,
                $node->getMaxAge(),
                CacheableInterface::CACHE_PUBLIC,
                true
            );
        } else {
            $cacheInfo = $this->get('open_orchestra_front.manager.node_response_manager')->getNodeCacheInfo($node);
            $privacy = ($cacheInfo['isPublic']) ? CacheableInterface::CACHE_PUBLIC : CacheableInterface::CACHE_PRIVATE;

            $response = $cacheableManager->setResponseCacheParameters(
                $response,
                $cacheInfo['MaxAge'],
                $privacy
            );
        }

        return $response;
    }

    /**
     * Render the node version given by encoded $token
     *
     * @param string  $token
     *
     * @return Response
     */
    public function previewAction($token)
    {
        $decryptedToken = $this->get('open_orchestra_base.manager.encryption')->decrypt($token);
        /** @var NodeInterface $node */
        $node = $this->get('open_orchestra_model.repository.node')->findVersionByDocumentId($decryptedToken);

        $siteManager = $this->get('open_orchestra_display.manager.site');
        $siteManager->setSiteId($node->getSiteId());
        $siteManager->setCurrentLanguage($node->getLanguage());

        return $this->renderNode($node, array('token' => $token));
    }

    /**
     * @param ReadNodeInterface $node
     * @param array             $parameters
     *
     * @return Response
     */
    protected function renderNode(ReadNodeInterface $node, array $parameters = array())
    {
        $response = $this->render(
            $this->getTemplate($node),
            array(
                'node' => $node,
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
