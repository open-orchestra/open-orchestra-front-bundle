<?php

namespace OpenOrchestra\FrontBundle\Routing\Database\Transformer;

use OpenOrchestra\ModelInterface\Model\RouteDocumentInterface;
use Symfony\Component\Routing\Route;

/**
 * Class RouteDocumentToValueObjectTransformer
 */
class RouteDocumentToValueObjectTransformer
{
    /**
     * @param RouteDocumentInterface $routeDocument
     *
     * @return Route
     */
    public function transform(RouteDocumentInterface $routeDocument)
    {
        $defaults = array_merge(array('_controller'=> 'OpenOrchestra\FrontBundle\Controller\NodeController::showAction'),$routeDocument->getDefaults());
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
