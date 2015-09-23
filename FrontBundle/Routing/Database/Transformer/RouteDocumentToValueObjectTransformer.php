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
        return new Route(
            $routeDocument->getPattern(),
            $routeDocument->getDefaults(),
            $routeDocument->getRequirements(),
            $routeDocument->getOptions(),
            $routeDocument->getHost(),
            $routeDocument->getSchemes(),
            $routeDocument->getMethods(),
            $routeDocument->getCondition()
        );
    }
}
