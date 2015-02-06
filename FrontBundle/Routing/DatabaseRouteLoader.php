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
                $nodes = $this->nodeRepository->findLastPublishedVersionByLanguageAndSiteId($language, $site->getSiteId());
                /** @var NodeInterface $node */
                foreach ($nodes as $node) {
                    /** @var SiteAliasInterface $alias */
                    foreach ($site->getAliases() as $key => $alias) {
                        if (in_array($node->getLanguage(), $alias->getLanguages())) {
                            $route = new Route(
                                $node->getRoutePattern(),
                                array(
                                    '_controller' => 'PHPOrchestra\FrontBundle\Controller\NodeController::showAction',
                                    '_locale' => $node->getLanguage(),
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

}
