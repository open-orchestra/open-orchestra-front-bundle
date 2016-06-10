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

    public function __construct(RouterInterface $router)
    {
        $defaults = $router->getRouteCollection()->get('open_orchestra_front_node')->getDefaults();
        if (is_array($defaults) && array_key_exists('_controller', $defaults)) {
            $this->logicalName = $defaults['_controller'];
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
            $routeDocument->getPattern(),
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
