<?php

namespace PHPOrchestra\FrontBundle\Manager;

use PHPOrchestra\BaseBundle\Context\CurrentSiteIdInterface;
use PHPOrchestra\ModelInterface\Model\NodeInterface;
use PHPOrchestra\ModelInterface\Repository\NodeRepositoryInterface;
use PHPOrchestra\ModelInterface\Repository\SiteRepositoryInterface;

/**
 * Class DynamicRoutingManager
 */
class DynamicRoutingManager
{
    protected $nodeRepository;
    protected $siteRepository;
    protected $siteManager;

    /**
     * @param NodeRepositoryInterface $nodeRepository
     * @param CurrentSiteIdInterface  $siteManager
     * @param SiteRepositoryInterface $siteRepository
     */
    public function __construct(NodeRepositoryInterface $nodeRepository, CurrentSiteIdInterface $siteManager, SiteRepositoryInterface $siteRepository)
    {
        $this->nodeRepository = $nodeRepository;
        $this->siteRepository = $siteRepository;
        $this->siteManager = $siteManager;
    }

    /**
     * @param string $pathInfo
     *
     * @deprecated use dynamic routing
     *
     * @return array
     */
    public function getRouteParameterFromRequestPathInfo($pathInfo)
    {
        $slugs = explode('/', $pathInfo);
        $slugs = array_filter($slugs, function($param) {
            return '' != $param;
        });

        $nodeId = NodeInterface::ROOT_NODE_ID;
        $nodeFound = false;
        $parameters = $slugs;

        $locale = $this->siteManager->getCurrentSiteDefaultLanguage();
        foreach ($slugs as $position => $slug) {
            if (1 == $position && in_array($slug, $this->findLanguagesAccepted())) {
                $locale = $slug;
                $parameters = array_slice($slugs, $position);
            } else {
                $node = $this->getNode($slug, $nodeId);
                if ($node) {
                    $nodeId = $node->getNodeId();
                    $nodeFound = true;
                    $parameters = array_slice($slugs, $position);
                } elseif ($nodeFound) {
                    break;
                }
            }
        }

        return array(
            "_route" => "php_orchestra_front_node",
            "_controller" => 'PHPOrchestra\FrontBundle\Controller\NodeController::showAction',
            "_locale" => $locale,
            "nodeId" => $nodeId,
            "module_parameters" => $parameters
        );
    }

    /**
     * @param string $slug
     * @param string $parentId
     *
     * @deprecated use dynamic routing
     *
     * @return mixed
     */
    protected function getNode($slug, $parentId)
    {
        $siteId = $this->siteManager->getCurrentSiteId();

        return $this->nodeRepository->findOneByParendIdAndRoutePatternAndSiteId((string) $parentId, $slug, $siteId);
    }

    /**
     * @deprecated use dynamic routing
     *
     * @return array
     */
    protected function findLanguagesAccepted()
    {
        $site = $this->siteRepository->findOneBySiteId($this->siteManager->getCurrentSiteId());

        return $site->getLanguages();
    }
}
