<?php

namespace OpenOrchestra\FrontBundle\Routing;

use OpenOrchestra\FrontBundle\Manager\NodeManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class OpenOrchestraUrlGenerator
 */
class OpenOrchestraUrlGenerator extends UrlGenerator
{
    protected $nodeRepository;
    protected $request;
    protected $siteManager;
    protected $nodeManager;

    /**
     * Constructor
     *
     * @param RouteCollection $routes
     * @param RequestContext  $context
     * @param RequestStack    $requestStack
     * @param NodeManager     $nodeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        RouteCollection $routes,
        RequestContext $context,
        RequestStack $requestStack,
        NodeManager $nodeManager,
        LoggerInterface $logger = null
    )
    {
        $this->request = $requestStack->getMasterRequest();
        $this->context = $context;
        $this->routes = $routes;
        $this->nodeManager = $nodeManager;
        $this->logger = $logger;
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
        if (isset($parameters['redirect_to_language'])) {
            $name = $this->nodeManager->getNodeRouteName($name, $parameters['redirect_to_language']);
        }

        try {
            $uri = parent::generate($name, $parameters, $referenceType);
        } catch (RouteNotFoundException $e) {
            $aliasId = 0;
            if ($this->request) {
                $aliasId = $this->request->get('aliasId', $aliasId);
            }
            $uri = parent::generate($aliasId . '_' . $name, $parameters, $referenceType);
        }

        return $uri;
    }
}
