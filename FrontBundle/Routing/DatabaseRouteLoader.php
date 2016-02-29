<?php

namespace OpenOrchestra\FrontBundle\Routing;

use OpenOrchestra\ModelInterface\Model\ReadNodeInterface;
use OpenOrchestra\ModelInterface\Model\SchemeableInterface;
use OpenOrchestra\ModelInterface\Model\ReadSiteAliasInterface;
use OpenOrchestra\ModelInterface\Model\ReadSiteInterface;
use OpenOrchestra\ModelInterface\Repository\ReadNodeRepositoryInterface;
use OpenOrchestra\ModelInterface\Repository\ReadSiteRepositoryInterface;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class DatabaseRouteLoader
 */
class DatabaseRouteLoader extends Loader
{
    protected $nodeRepository;
    protected $siteRepository;
    protected $orderedNodes = array();

    /**
     * @param ReadNodeRepositoryInterface $nodeRepository
     * @param ReadSiteRepositoryInterface $siteRepository
     */
    public function __construct(ReadNodeRepositoryInterface $nodeRepository, ReadSiteRepositoryInterface $siteRepository)
    {
        $this->nodeRepository = $nodeRepository;
        $this->siteRepository = $siteRepository;
    }

    /**
     * Loads a resource.
     *
     * @param mixed  $resource The resource
     * @param string $type     The resource type
     *
     * @return RouteCollection
     */
    public function load($resource, $type = null)
    {
        $routes = new RouteCollection();

        $sites = $this->siteRepository->findByDeleted(false);
        /** @var ReadSiteInterface $site */
        foreach ($sites as $site) {
            foreach ($site->getLanguages() as $language) {
                $nodes = $this->initializeNodes($language, $site);
                /** @var ReadNodeInterface $node */
                foreach ($nodes as $node) {
                    $this->generateRoutesForNode($site, $node, $routes);

                }
            }
        }

        return $routes;
    }

    /**
     * Returns true if this class supports the given resource.
     *
     * @param mixed $resource A resource
     * @param string $type The resource type
     *
     * @return bool    true if this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null)
    {
        return 'database' === $type;
    }

    /**
     * @param ReadNodeInterface $node
     *
     * @return string
     */
    protected function generateRoutePattern(ReadNodeInterface $node)
    {
        $routePattern = $node->getRoutePattern();
        $parentId = $node->getParentId();
        if (is_null($parentId) || strstr($routePattern, '/') || !array_key_exists($parentId, $this->orderedNodes)) {
            return $routePattern;
        }

        return $this->suppressDoubleSlashes($this->generateRoutePattern($this->orderedNodes[$parentId]) . '/' . $routePattern);
    }

    /**
     * @param string $route
     *
     * @return string
     */
    protected function suppressDoubleSlashes($route)
    {
        return str_replace('//', '/', $route);
    }

    /**
     * @param array $nodes
     */
    protected function orderNodes(array $nodes)
    {
        /** @var ReadNodeInterface $node */
        foreach ($nodes as $node) {
            $this->orderedNodes[$node->getNodeId()] = $node;
        }
    }

    /**
     * @param string            $language
     * @param ReadSiteInterface $site
     * @return array
     */
    protected function initializeNodes($language, ReadSiteInterface $site)
    {
        $this->orderedNodes = array();
        $nodes = $this->nodeRepository->findCurrentlyPublishedVersion($language, $site->getSiteId());
        $this->orderNodes($nodes);

        return $nodes;
    }

    /**
     * @param ReadSiteInterface $site
     * @param ReadNodeInterface $node
     * @param RouteCollection   $routes
     */
    protected function generateRoutesForNode(ReadSiteInterface $site, ReadNodeInterface $node, RouteCollection $routes)
    {
        /** @var ReadSiteAliasInterface $alias */
        foreach ($site->getAliases() as $key => $alias) {
            $nodeLanguage = $node->getLanguage();
            if ($nodeLanguage == $alias->getLanguage()) {
                $pattern = $this->generateRoutePattern($node);
                if ($alias->getPrefix()) {
                    $pattern = $this->suppressDoubleSlashes($alias->getPrefix() . '/' . $pattern);
                }
                $scheme = $node->getScheme();
                if (is_null($scheme) || SchemeableInterface::SCHEME_DEFAULT == $scheme) {
                    $scheme = $alias->getScheme();
                }
                $nodeId = $node->getNodeId();
                $siteId = $site->getSiteId();
                $domain = $alias->getDomain();
                $route = $this->generateRoute($pattern, $nodeLanguage, $nodeId, $siteId, $key, $domain, $scheme);
                $routes->add($key . '_' . $node->getId(), $route);
            }
        }
    }

    /**
     * @param string $pattern
     * @param string $nodeLanguage
     * @param string $nodeId
     * @param string $siteId
     * @param string $key
     * @param string $domain
     * @param string $scheme
     *
     * @return Route
     */
    protected function generateRoute($pattern, $nodeLanguage, $nodeId, $siteId, $key, $domain, $scheme)
    {
        $route = new Route(
            $pattern,
            array(
                '_controller' => 'OpenOrchestra\FrontBundle\Controller\NodeController::showAction',
                '_locale' => $nodeLanguage,
                'nodeId' => $nodeId,
                'siteId' => $siteId,
                'aliasId' => $key,
            ),
            array(),
            array(),
            $domain,
            $scheme
        );

        return $route;
    }
}
