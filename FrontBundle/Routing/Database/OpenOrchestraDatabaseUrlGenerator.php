<?php

namespace OpenOrchestra\FrontBundle\Routing\Database;

use OpenOrchestra\DisplayBundle\Exception\NodeNotFoundException;
use OpenOrchestra\DisplayBundle\Manager\ContextInterface;
use OpenOrchestra\FrontBundle\Manager\NodeManager;
use OpenOrchestra\FrontBundle\Routing\Database\Transformer\RouteDocumentToValueObjectTransformer;
use OpenOrchestra\ModelInterface\Model\RouteDocumentInterface;
use OpenOrchestra\ModelInterface\Repository\RouteDocumentRepositoryInterface;
use OpenOrchestra\ModelInterface\Repository\ReadSiteRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;

/**
 * Class OpenOrchestraDatabaseUrlGenerator
 */
class OpenOrchestraDatabaseUrlGenerator extends UrlGenerator
{
    protected $requestStack;
    protected $nodeManager;
    protected $routeDocumentRepository;
    protected $siteRepository;
    protected $routeDocumentToValueObjectTransformer;
    protected $currentSiteManager;
    const REDIRECT_TO_LANGUAGE = 'redirect_to_language';

    /**
     * Constructor.
     *
     * @param RouteDocumentRepositoryInterface      $routeDocumentRepository
     * @param ReadSiteRepositoryInterface           $siteRepository
     * @param RouteDocumentToValueObjectTransformer $routeDocumentToValueObjectTransformer
     * @param ContextInterface                      $currentSiteManager
     * @param RequestStack                          $requestStack
     * @param NodeManager                           $nodeManager
     * @param RequestContext                        $context The context
     * @param LoggerInterface|null                  $logger  A logger instance
     */
    public function __construct(
        RouteDocumentRepositoryInterface $routeDocumentRepository,
        ReadSiteRepositoryInterface $siteRepository,
        RouteDocumentToValueObjectTransformer $routeDocumentToValueObjectTransformer,
        ContextInterface $currentSiteManager,
        RequestStack $requestStack,
        NodeManager $nodeManager,
        RequestContext $context,
        LoggerInterface $logger = null)
    {
        $this->routeDocumentToValueObjectTransformer = $routeDocumentToValueObjectTransformer;
        $this->routeDocumentRepository = $routeDocumentRepository;
        $this->siteRepository = $siteRepository;
        $this->currentSiteManager = $currentSiteManager;
        $this->requestStack = $requestStack;
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
    public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
    {
        $request = $this->requestStack->getMasterRequest();
        if (isset($parameters[self::REDIRECT_TO_LANGUAGE])) {
            try {
                $fullName = $this->nodeManager->getNodeRouteName($name, $parameters[self::REDIRECT_TO_LANGUAGE]);
            } catch (NodeNotFoundException $e) {
                throw new RouteNotFoundException(sprintf('Unable to generate a URL for the named route "%s" as such route does not exist.', $name));
            }
            unset($parameters[self::REDIRECT_TO_LANGUAGE]);
        } else {
            if (null === $request) {
                throw new RouteNotFoundException(sprintf('Unable to generate a URL for the named route "%s" as such route does not exist.', $name));
            }

            if (null !== $request->get('aliasId')) {
                $aliasId = $request->get('aliasId');
            } else {
                $site = $this->siteRepository->findOneBySiteId($this->currentSiteManager->getSiteId());
                if (null === $site) {
                    throw new RouteNotFoundException(sprintf('Unable to generate a URL for the named route "%s" as such route does not exist.', $name));
                }
                $aliasId = $site->getMainAliasId();
            }

            $fullName = $aliasId . '_' . $name;
        }

        $routeDocument = $this->routeDocumentRepository->findOneByName($fullName);

        if (!$routeDocument instanceof RouteDocumentInterface) {
            throw new RouteNotFoundException(sprintf('The route %s does not exists in the database', $fullName));
        }

        $route = $this->routeDocumentToValueObjectTransformer->transform($routeDocument);

        $compiledRoute = $route->compile();

        return $this->doGenerate(
            $compiledRoute->getVariables(),
            $route->getDefaults(),
            $route->getRequirements(),
            $compiledRoute->getTokens(),
            $parameters,
            $fullName,
            $referenceType,
            $compiledRoute->getHostTokens(),
            $route->getSchemes()
        );
    }
}
