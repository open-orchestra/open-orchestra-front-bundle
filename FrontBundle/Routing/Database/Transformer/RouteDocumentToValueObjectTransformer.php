<?php

namespace OpenOrchestra\FrontBundle\Routing\Database\Transformer;

use OpenOrchestra\ModelInterface\Model\RouteDocumentInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;
use OpenOrchestra\FrontBundle\Exception\NoRenderingMethodForNodeException;

/**
 * Class RouteDocumentToValueObjectTransformer
 */
class RouteDocumentToValueObjectTransformer
{
    protected $logicalName;

    /**
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $route = $router->getRouteCollection()->get('open_orchestra_front_node');
        if (!is_null($route) && !is_null($route->getDefault('_controller'))) {
            $this->logicalName = $route->getDefault('_controller');
        } else {
            throw new NoRenderingMethodForNodeException();
        }
    }

    /**
     * @param RouteDocumentInterface $routeDocument
     *
     * @return Route
     */
    public function transform(RouteDocumentInterface $routeDocument)
    {
        $defaults = array_merge(array('_controller' => $this->logicalName), $routeDocument->getDefaults());
        return new Route(
            rtrim($routeDocument->getPattern(), '/'),
            $defaults,
            $routeDocument->getRequirements(),
            $routeDocument->getOptions(),
            $routeDocument->getHost(),
            $routeDocument->getSchemes(),
            $routeDocument->getMethods(),
            $routeDocument->getCondition()
        );
    }
}
