<?php

namespace PHPOrchestra\FrontBundle\Manager;

use PHPOrchestra\BaseBundle\Context\CurrentSiteIdInterface;
use PHPOrchestra\ModelInterface\Model\NodeInterface;
use PHPOrchestra\ModelBundle\Repository\NodeRepository;
use PHPOrchestra\ModelBundle\Repository\SiteRepository;

/**
 * Class DynamicRoutingManager
 */
class DynamicRoutingManager
{
    protected $nodeRepository;
    protected $siteRepository;
    protected $siteManager;

    /**
     * @param NodeRepository         $nodeRepository
     * @param CurrentSiteIdInterface $siteManager
     * @param SiteRepository         $siteRepository
     */
    public function __construct(NodeRepository $nodeRepository, CurrentSiteIdInterface $siteManager, SiteRepository $siteRepository)
    {
        $this->nodeRepository = $nodeRepository;
        $this->siteRepository = $siteRepository;
        $this->siteManager = $siteManager;
    }

    /**
     * @param string $pathInfo
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
     * @param $slug
     * @param $parentId
     * @return mixed
     */
    protected function getNode($slug, $parentId)
    {
        $criteria = array(
            'parentId' => (string) $parentId,
            'alias' => $slug,
            'siteId' => $this->siteManager->getCurrentSiteId(),
        );

        return $this->nodeRepository->findOneBy($criteria);
    }

    protected function findLanguagesAccepted()
    {
        $site = $this->siteRepository->findOneBySiteId($this->siteManager->getCurrentSiteId());

        return $site->getLanguages();
    }
}
