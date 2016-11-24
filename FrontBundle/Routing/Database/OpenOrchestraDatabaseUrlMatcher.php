<?php

namespace OpenOrchestra\FrontBundle\Routing\Database;

use OpenOrchestra\FrontBundle\Routing\Database\Transformer\RouteDocumentCollectionToRouteCollectionTransformer;
use OpenOrchestra\ModelInterface\Repository\RouteDocumentRepositoryInterface;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;

/**
 * Class OpenOrchestraDatabaseUrlMatcher
 */
class OpenOrchestraDatabaseUrlMatcher extends UrlMatcher
{
    protected $routeCollectionTransformer;
    protected $routeRepository;

    /**
     * Constructor.
     *
     * @param RequestContext                                      $context The context
     * @param RouteDocumentCollectionToRouteCollectionTransformer $routeCollectionTransformer
     * @param RouteDocumentRepositoryInterface                    $routeRepository
     */
    public function __construct(
        RequestContext $context,
        RouteDocumentCollectionToRouteCollectionTransformer $routeCollectionTransformer,
        RouteDocumentRepositoryInterface $routeRepository
    ){
        $this->context = $context;
        $this->routeCollectionTransformer = $routeCollectionTransformer;
        $this->routeRepository = $routeRepository;
    }

    /**
     * Tries to match a URL path with a set of routes.
     *
     * @param string $pathinfo The path info to be parsed (raw format, i.e. not urldecoded)
     *
     * @return array An array of parameters
     *
     * @throws ResourceNotFoundException If the resource could not be found
     * @throws MethodNotAllowedException If the resource was found but the request method is not allowed
     */
    public function match($pathinfo)
    {
        $this->allow = array();

        $pathinfo = rtrim(rawurldecode($pathinfo), '/');
        $routeCollection = $this->routeCollectionTransformer->transform($this->routeRepository->findByPathInfo($pathinfo));

        if ('' == $pathinfo) {
            $pathinfo = '/';
        }
        if ($ret = $this->matchCollection($pathinfo, $routeCollection)) {
            return $ret;
        }

        throw 0 < count($this->allow)
            ? new MethodNotAllowedException(array_unique($this->allow))
            : new ResourceNotFoundException(sprintf('No routes found for "%s".', $pathinfo));
    }
}
