<?php

namespace OpenOrchestra\FrontBundle\Routing\Database\Transformer;

use Doctrine\Common\Collections\Collection;
use OpenOrchestra\ModelInterface\Model\RouteDocumentInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteDocumentCollectionToRouteCollectionTransformer
 */
class RouteDocumentCollectionToRouteCollectionTransformer
{
    protected $routeTransformer;

    /**
     * @param RouteDocumentToValueObjectTransformer $routeTransformer
     */
    public function __construct(RouteDocumentToValueObjectTransformer $routeTransformer)
    {
        $this->routeTransformer = $routeTransformer;
    }

    /**
     * @param Collection $routeDocuments
     *
     * @return RouteCollection
     */
    public function transform(Collection $routeDocuments)
    {
        $routeCollection = new RouteCollection();

        /** @var RouteDocumentInterface $routeDocument */
        foreach ($routeDocuments as $routeDocument) {
            $routeCollection->add($routeDocument->getName(), $this->routeTransformer->transform($routeDocument));
        }

        return $routeCollection;
    }
}
