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

/**
 * Class OpenOrchestraUrlGenerator
 */
class OpenOrchestraUrlGenerator extends UrlGenerator
{
    protected $nodeRepository;
    protected $request;
    protected $siteManager;
    protected $nodeManager;
    const REDIRECT_TO_LANGUAGE = 'redirect_to_language';

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
        parent::__construct($routes, $context, $logger);
        $this->request = $requestStack->getMasterRequest();
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
