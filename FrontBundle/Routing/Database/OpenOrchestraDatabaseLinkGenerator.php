<?php

namespace OpenOrchestra\FrontBundle\Routing\Database;

use OpenOrchestra\FrontBundle\Manager\NodeManager;
use OpenOrchestra\FrontBundle\Routing\Database\Transformer\RouteDocumentToValueObjectTransformer;
use OpenOrchestra\ModelInterface\Model\RouteDocumentInterface;
use OpenOrchestra\ModelInterface\Repository\RouteDocumentRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;

/**
 * Class OpenOrchestraDatabaseLinkGenerator
 */
class OpenOrchestraDatabaseLinkGenerator extends UrlGenerator
{
    protected $request;
    protected $nodeManager;
    protected $routeDocumentRepository;
    protected $routeDocumentToValueObjectTransformer;
    const REDIRECT_TO_LANGUAGE = 'redirect_to_language';

    /**
     * Constructor.
     *
     * @param RouteDocumentRepositoryInterface      $routeDocumentRepository
     * @param RouteDocumentToValueObjectTransformer $routeDocumentToValueObjectTransformer
     * @param RequestStack                          $requestStack
     * @param NodeManager                           $nodeManager
     * @param RequestContext                        $context The context
     * @param LoggerInterface|null                  $logger  A logger instance
     */
    public function __construct(
        RouteDocumentRepositoryInterface $routeDocumentRepository,
        RouteDocumentToValueObjectTransformer $routeDocumentToValueObjectTransformer,
        RequestStack $requestStack,
        NodeManager $nodeManager,
        RequestContext $context,
        LoggerInterface $logger = null)
    {
        $this->routeDocumentToValueObjectTransformer = $routeDocumentToValueObjectTransformer;
        $this->routeDocumentRepository = $routeDocumentRepository;
        $this->request = $requestStack->getMasterRequest();
        $this->nodeManager = $nodeManager;
        $this->context = $context;
        $this->logger = $logger;
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
        $routeDocument = $this->routeDocumentRepository->findOneByName($name);

        if (!$routeDocument instanceof RouteDocumentInterface) {
            throw new RouteNotFoundException(sprintf('The route %s does not exists in the database', $name));
        }

        $route = $this->routeDocumentToValueObjectTransformer->transform($routeDocument);
        $compiledRoute = $route->compile();

        return $this->doGenerate(
            $compiledRoute->getVariables(),
            $route->getDefaults(),
            $route->getRequirements(),
            $compiledRoute->getTokens(),
            $parameters,
            $name,
            $referenceType,
            $compiledRoute->getHostTokens(),
            $route->getSchemes()
        );
    }
}
