<?php

namespace PHPOrchestra\FrontBundle\Routing;

use PHPOrchestra\ModelInterface\Model\NodeInterface;
use PHPOrchestra\ModelInterface\Model\SiteAliasInterface;
use PHPOrchestra\ModelInterface\Model\SiteInterface;
use PHPOrchestra\ModelInterface\Repository\NodeRepositoryInterface;
use PHPOrchestra\ModelInterface\Repository\SiteRepositoryInterface;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class DatabaseRouteLoader
 */
class DatabaseRouteLoader extends Loader
{
    protected $loaded = false;
    protected $nodeRepository;
    protected $siteRepository;
    protected $orderedNodes = array();

    /**
     * @param NodeRepositoryInterface $nodeRepository
     * @param SiteRepositoryInterface $siteRepository
     */
    public function __construct(NodeRepositoryInterface $nodeRepository, SiteRepositoryInterface $siteRepository)
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
        if (true === $this->loaded) {
            throw new \RuntimeException('Do not add the "database" loader twice');
        }

        $routes = new RouteCollection();

        $sites = $this->siteRepository->findByDeleted(false);
        /** @var SiteInterface $site */
        foreach ($sites as $site) {
            foreach ($site->getLanguages() as $language) {
                $nodes = $this->initializeNodes($language, $site);
                /** @var NodeInterface $node */
                foreach ($nodes as $node) {
                    $this->generateRoutesForNode($site, $node, $routes);

                }
            }
        }

        $this->loaded = true;

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
     * @param NodeInterface $node
     *
     * @return string
     */
    protected function generateRoutePattern(NodeInterface $node)
    {
        if (is_null($node->getParentId()) || !array_key_exists($node->getParentId(), $this->orderedNodes)) {
            return $node->getRoutePattern();
        }

        return $this->suppressDoubleSlashes($this->generateRoutePattern($this->orderedNodes[$node->getParentId()]) . '/' . $node->getRoutePattern());
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
        /** @var NodeInterface $node */
        foreach ($nodes as $node) {
            $this->orderedNodes[$node->getNodeId()] = $node;
        }
    }

    /**
     * @param $language
     * @param $site
     * @return array
     */
    protected function initializeNodes($language, $site)
    {
        $this->orderedNodes = array();
        $nodes = $this->nodeRepository->findLastPublishedVersionByLanguageAndSiteId($language, $site->getSiteId());
        $this->orderNodes($nodes);

        return $nodes;
    }

    /**
     * @param SiteInterface   $site
     * @param NodeInterface   $node
     * @param RouteCollection $routes
     */
    protected function generateRoutesForNode($site, $node, $routes)
    {
        /** @var SiteAliasInterface $alias */
        foreach ($site->getAliases() as $key => $alias) {
            $nodeLanguage = $node->getLanguage();
            if ($nodeLanguage == $alias->getLanguage()) {
                $pattern = $this->generateRoutePattern($node);
                if ($alias->getPrefix()) {
                    $pattern = $this->suppressDoubleSlashes($alias->getPrefix() . '/' . $pattern);
                }
                $route = new Route(
                    $pattern,
                    array(
                        '_controller' => 'PHPOrchestra\FrontBundle\Controller\NodeController::showAction',
                        '_locale' => $nodeLanguage,
                        'nodeId' => $node->getNodeId(),
                        'siteId' => $site->getSiteId(),
                        'aliasId' => $key,
                    ),
                    array(),
                    array(),
                    $alias->getDomain()
                );
                $routes->add($key . '_' . $node->getId(), $route);
            }
        }
    }
}
