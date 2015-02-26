<?php

namespace OpenOrchestra\FrontBundle\Routing;

use OpenOrchestra\BaseBundle\Context\CurrentSiteIdInterface;
use OpenOrchestra\ModelInterface\Model\NodeInterface;
use OpenOrchestra\ModelInterface\Repository\NodeRepositoryInterface;
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

    /**
     * Constructor
     *
     * @param RouteCollection $routes
     * @param RequestContext  $context
     * @param RequestStack    $requestStack
     * @param LoggerInterface $logger
     */
    public function __construct(
        RouteCollection $routes,
        RequestContext $context,
        RequestStack $requestStack,
        LoggerInterface $logger = null
    )
    {
        $this->request = $requestStack->getMasterRequest();
        $this->context = $context;
        $this->routes = $routes;
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
        try {
            $uri = parent::generate($name, $parameters, $referenceType);
        } catch (RouteNotFoundException $e) {
            $aliasId = 0;
            if ($this->request) {
                $aliasId = $this->request->get('aliasId', '0');
            }
            $uri = parent::generate($aliasId . '_' . $name, $parameters, $referenceType);
        }

        return $uri;
    }
}
