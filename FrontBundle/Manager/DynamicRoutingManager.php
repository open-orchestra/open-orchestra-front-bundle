<?php

namespace PHPOrchestra\FrontBundle\Manager;

use PHPOrchestra\ModelBundle\Model\NodeInterface;
use PHPOrchestra\ModelBundle\Repository\NodeRepository;

/**
 * Class DynamicRoutingManager
 */
class DynamicRoutingManager
{
    protected $nodeRepository;

    /**
     * @param NodeRepository $nodeRepository
     */
    public function __construct(NodeRepository $nodeRepository)
    {
        $this->nodeRepository = $nodeRepository;
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

        foreach ($slugs as $position => $slug) {
            $node = $this->getNode($slug, $nodeId);
            if ($node) {
                $nodeId = $node->getNodeId();
                $nodeFound = true;
                $parameters = array_slice($slugs, $position);
            } elseif ($nodeFound) {
                break;
            }
        }

        return array(
            "_route" => "php_orchestra_front_node",
            "_controller" => 'PHPOrchestra\FrontBundle\Controller\NodeController::showAction',
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
            'alias' => $slug
        );

        return $this->nodeRepository->findOneBy($criteria);
    }
}