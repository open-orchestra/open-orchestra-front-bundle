<?php

namespace OpenOrchestra\FrontBundle\Routing\Database;

use OpenOrchestra\FrontBundle\Manager\NodeManager;
use OpenOrchestra\FrontBundle\Routing\Database\Transformer\RouteDocumentCollectionToRouteCollectionTransformer;
use OpenOrchestra\FrontBundle\Routing\Database\Transformer\RouteDocumentToValueObjectTransformer;
use OpenOrchestra\ModelInterface\Repository\RouteDocumentRepositoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use OpenOrchestra\ModelInterface\Repository\ReadSiteRepositoryInterface;
use OpenOrchestra\BaseBundle\Context\CurrentSiteIdInterface;

/**
 * Class OpenOrchestraDatabaseRouter
 */
class OpenOrchestraDatabaseRouter implements RouterInterface
{
    /**
     * @var RequestContext
     */
    protected $context;

    protected $siteRepository;
    protected $currentSiteManager;
    protected $routeDocumentCollectionToRouteCollectionTransformer;
    protected $routeDocumentToValueObjectTransformer;
    protected $routeDocumentRepository;
    protected $options = array();
    protected $routeCollection;
    protected $requestStack;
    protected $nodeManager;
    protected $generator;
    protected $matcher;

    /**
     * @param ReadSiteRepositoryInterface                         $siteRepository
     * @param CurrentSiteIdInterface                              $currentSiteManager
     * @param RouteDocumentRepositoryInterface                    $routeDocumentRepository
     * @param RouteDocumentToValueObjectTransformer               $routeDocumentToValueObjectTransformer
     * @param RouteDocumentCollectionToRouteCollectionTransformer $routeDocumentCollectionToRouteCollectionTransformer
     * @param RequestStack                                        $requestStack
     * @param NodeManager                                         $nodeManager
     * @param array                                               $options
     */
    public function __construct(
        ReadSiteRepositoryInterface $siteRepository,
        CurrentSiteIdInterface $currentSiteManager,
        RouteDocumentRepositoryInterface $routeDocumentRepository,
        RouteDocumentToValueObjectTransformer $routeDocumentToValueObjectTransformer,
        RouteDocumentCollectionToRouteCollectionTransformer $routeDocumentCollectionToRouteCollectionTransformer,
        RequestStack $requestStack,
        NodeManager $nodeManager,
        array $options = array()
    )
    {
        $this->siteRepository = $siteRepository;
        $this->currentSiteManager = $currentSiteManager;
        $this->routeDocumentCollectionToRouteCollectionTransformer = $routeDocumentCollectionToRouteCollectionTransformer;
        $this->routeDocumentToValueObjectTransformer = $routeDocumentToValueObjectTransformer;
        $this->routeDocumentRepository = $routeDocumentRepository;
        $this->requestStack = $requestStack;
        $this->nodeManager = $nodeManager;

        $this->options = array_merge(array(
            'generator_class' => 'OpenOrchestra\FrontBundle\Routing\Database\OpenOrchestraDatabaseUrlGenerator',
            'matcher_class' => 'OpenOrchestra\FrontBundle\Routing\Database\OpenOrchestraDatabaseUrlMatcher'
        ), $options);
    }

    /**
     * Sets the request context.
     *
     * @param RequestContext $context The context
     */
    public function setContext(RequestContext $context)
    {
        if (null !== $this->generator) {
            $this->getGenerator()->setContext($context);
        }
        $this->context = $context;
    }

    /**
     * Gets the request context.
     *
     * @return RequestContext The context
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Gets the RouteCollection instance associated with this Router.
     *
     * @return RouteCollection A RouteCollection instance
     */
    public function getRouteCollection()
    {
        if (null === $this->routeCollection) {
            $this->routeCollection = $this
                ->routeDocumentCollectionToRouteCollectionTransformer
                ->transform($this->routeDocumentRepository->findAll());
        }

        return $this->routeCollection;
    }

    /**
     * Generates a URL or path for a specific route based on the given parameters.
     *
     * @param string $name The name of the route
     * @param mixed $parameters An array of parameters
     * @param bool|string $referenceType The type of reference to be generated (one of the constants)
     *
     * @return string The generated URL
     *
     * @throws RouteNotFoundException              If the named route doesn't exist
     * @throws MissingMandatoryParametersException When some parameters are missing that are mandatory for the route
     * @throws InvalidParameterException           When a parameter value for a placeholder is not correct because
     *                                             it does not match the requirement
     */
    public function  generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
    {
        return $this->getGenerator()->generate($name, $parameters, $referenceType);
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
        return $this->getMatcher()->match($pathinfo);
    }

    /**
     * @return UrlGeneratorInterface
     */
    public function getGenerator()
    {
        if (!$this->generator instanceof UrlGeneratorInterface) {
            $generatorClass = $this->options['generator_class'];
            $this->generator = new $generatorClass(
                $this->siteRepository,
                $this->currentSiteManager,
                $this->routeDocumentRepository,
                $this->routeDocumentToValueObjectTransformer,
                $this->requestStack,
                $this->nodeManager,
                $this->context
            );
        }

        return $this->generator;
    }

    /**
     * @return UrlMatcherInterface
     */
    public function getMatcher()
    {
        if (!$this->matcher instanceof UrlMatcherInterface) {
            $matcherClass = $this->options['matcher_class'];
            $this->matcher = new $matcherClass(
                $this->context,
                $this->routeDocumentCollectionToRouteCollectionTransformer,
                $this->routeDocumentRepository
            );
        }

        return $this->matcher;
    }
}
