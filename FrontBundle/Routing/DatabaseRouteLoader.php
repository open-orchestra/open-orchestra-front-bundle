<?php

namespace PHPOrchestra\FrontBundle\Routing;

use PHPOrchestra\ModelInterface\Model\NodeInterface;
use PHPOrchestra\ModelInterface\Repository\NodeRepositoryInterface;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class DatabaseRouteLoader
 */
class DatabaseRouteLoader extends Loader
{
    protected $loaded = false;
    protected $nodeRepository;

    /**
     * @param NodeRepositoryInterface $nodeRepository
     */
    public function __construct(NodeRepositoryInterface $nodeRepository)
    {
        $this->nodeRepository = $nodeRepository;
    }

    /**
     * Loads a resource.
     *
     * @param mixed  $resource The resource
     * @param string $type     The resource type
     *
     * @return RouteCollection
     */
    public function load($resource, $type = null)
    {
        if (true === $this->loaded) {
            throw new \RuntimeException('Do not add the "database" loader twice');
        }

        $routes = new RouteCollection();

        $nodes = $this->nodeRepository->findByNodeType();
        /** @var NodeInterface $node */
        foreach ($nodes as $node) {
            $route = new Route(
                $node->getRoutePattern(),
                array(
                    '_controller' => 'PHPOrchestra\FrontBundle\Controller\NodeController::showAction',
                    'nodeId' => $node->getNodeId(),
                )
            );
            $routes->add($node->getId(), $route);
        }

        $this->loaded = true;

        return $routes;
    }

    /**
     * Returns true if this class supports the given resource.
     *
     * @param mixed $resource A resource
     * @param string $type The resource type
     *
     * @return bool    true if this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null)
    {
        return 'database' === $type;
    }

}
