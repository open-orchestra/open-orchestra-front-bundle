<?php

namespace OpenOrchestra\FrontBundle\Routing;

use OpenOrchestra\DisplayBundle\Exception\NodeNotFoundException;
use OpenOrchestra\FrontBundle\Manager\NodeManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use OpenOrchestra\ModelInterface\Repository\ReadSiteRepositoryInterface;
use OpenOrchestra\BaseBundle\Context\CurrentSiteIdInterface;

/**
 * Class OpenOrchestraUrlGenerator
 */
class OpenOrchestraUrlGenerator extends UrlGenerator
{
    protected $nodeRepository;
    protected $requestStack;
    protected $siteManager;
    protected $nodeManager;
    protected $siteRepository;
    protected $currentSiteManager;
    const REDIRECT_TO_LANGUAGE = 'redirect_to_language';

    /**
     * Constructor
     *
     * @param ReadSiteRepositoryInterface $siteRepository
     * @param CurrentSiteIdInterface      $currentSiteManager
     * @param RouteCollection             $routes
     * @param RequestContext              $context
     * @param RequestStack                $requestStack
     * @param NodeManager                 $nodeManager
     * @param LoggerInterface             $logger
     */
    public function __construct(
        ReadSiteRepositoryInterface $siteRepository,
        CurrentSiteIdInterface $currentSiteManager,
        RouteCollection $routes,
        RequestContext $context,
        RequestStack $requestStack,
        NodeManager $nodeManager,
        LoggerInterface $logger = null
    )
    {
        parent::__construct($routes, $context, $logger);
        $this->siteRepository = $siteRepository;
        $this->currentSiteManager = $currentSiteManager;
        $this->requestStack = $requestStack;
        $this->nodeManager = $nodeManager;
    }

    /**
     * @param string      $name
     * @param array       $parameters
     * @param bool|string $referenceType
     *
     * @return string
     */
    public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
    {
        if (isset($parameters[self::REDIRECT_TO_LANGUAGE])) {
            try {
                $name = $this->nodeManager->getNodeRouteName($name, $parameters[self::REDIRECT_TO_LANGUAGE]);
            } catch (NodeNotFoundException $e) {
                throw new RouteNotFoundException(sprintf('Unable to generate a URL for the named route "%s" as such route does not exist.', $name));
            }
            unset($parameters[self::REDIRECT_TO_LANGUAGE]);

            return parent::generate($name, $parameters, $referenceType);
        }
        $site = $this->siteRepository->findOneBySiteId($this->currentSiteManager->getCurrentSiteId());

        if (null === $site) {
            throw new RouteNotFoundException(sprintf('Unable to generate a URL for the named route "%s" as such route does not exist.', $name));
        }
        $aliasId = $site->getMainAliasId();
        if ($this->request) {
            $aliasId = $this->request->get('aliasId', $aliasId);
        }
        $uri = parent::generate($aliasId . '_' . $name, $parameters, $referenceType);

        return $uri;
    }
}
