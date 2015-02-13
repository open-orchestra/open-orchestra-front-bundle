<?php

namespace PHPOrchestra\FrontBundle\Routing;

use PHPOrchestra\ModelInterface\Model\NodeInterface;
use PHPOrchestra\ModelInterface\Model\RedirectionInterface;
use PHPOrchestra\ModelInterface\Model\SiteAliasInterface;
use PHPOrchestra\ModelInterface\Model\SiteInterface;
use PHPOrchestra\ModelInterface\Repository\NodeRepositoryInterface;
use PHPOrchestra\ModelInterface\Repository\RedirectionRepositoryInterface;
use PHPOrchestra\ModelInterface\Repository\SiteRepositoryInterface;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RedirectionLoader
 */
class RedirectionLoader extends Loader
{
    protected $redirectionRepository;
    protected $nodeRepository;
    protected $siteRepository;
    protected $loaded = false;

    /**
     * @param RedirectionRepositoryInterface $redirectionRepository
     * @param NodeRepositoryInterface        $nodeRepository
     * @param SiteRepositoryInterface        $siteRepository
     */
    public function __construct(RedirectionRepositoryInterface $redirectionRepository, NodeRepositoryInterface $nodeRepository, SiteRepositoryInterface $siteRepository)
    {
        $this->redirectionRepository = $redirectionRepository;
        $this->nodeRepository = $nodeRepository;
        $this->siteRepository = $siteRepository;
    }

    /**
     * Loads a resource.
     *
     * @param mixed $resource The resource
     * @param string|null $type The resource type or null if unknown
     *
     * @throws \Exception If something went wrong
     *
     * @return RouteCollection
     */
    public function load($resource, $type = null)
    {
        if (true === $this->loaded) {
            throw new \RuntimeException('Do not add the "database" loader twice');
        }

        $routes = new RouteCollection();

        $redirections = $this->redirectionRepository->findAll();

        /** @var RedirectionInterface $redirection */
        foreach ($redirections as $redirection) {
            $site = $this->siteRepository->findOneBySiteId($redirection->getSiteId());
            if ($redirection->getNodeId()) {
                /** @var NodeInterface $node */
                $node = $this->nodeRepository->findOneByNodeIdAndLanguageWithPublishedAndLastVersionAndSiteId($redirection->getNodeId(), $redirection->getLocale(), $redirection->getSiteId());
                $parameterKey = 'route';
                $nodeId = $node->getId();
                $this->generateRouteForSite($site, $redirection, $parameterKey, $nodeId, null, $routes);
            } elseif ($redirection->getUrl()) {
                $parameterKey = 'path';
                $this->generateRouteForSite($site, $redirection, $parameterKey, null, $redirection->getUrl(), $routes);
            }
        }

        $this->loaded = true;

        return $routes;

    }

    /**
     * Returns whether this class supports the given resource.
     *
     * @param mixed $resource A resource
     * @param string|null $type The resource type or null if unknown
     *
     * @return bool True if this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null)
    {
        return 'orchestra_redirection' === $type;
    }

    /**
     * @param SiteInterface        $site
     * @param RedirectionInterface $redirection
     * @param string               $parameterKey
     * @param string|null          $nodeId
     * @param string|null          $url
     * @param RouteCollection      $routes
     */
    protected function generateRouteForSite(SiteInterface $site, RedirectionInterface $redirection, $parameterKey, $nodeId = null, $url = null, RouteCollection $routes)
    {
        /** @var SiteAliasInterface $alias */
        foreach ($site->getAliases() as $key => $alias) {
            if ($redirection->getLocale() === $alias->getLanguage()) {
                $parameter = $url;
                $controller = 'FrameworkBundle:Redirect:urlRedirect';
                if (!is_null($nodeId)) {
                    $parameter = $key . '_' . $nodeId;
                    $controller = 'FrameworkBundle:Redirect:redirect';
                }
                $route = new Route(
                    $redirection->getRoutePattern(),
                    array(
                        '_controller' => $controller,
                        $parameterKey => $parameter,
                        'permanent' => $redirection->isPermanent(),
                    ),
                    array(),
                    array(),
                    $alias->getDomain()
                );
                $routes->add($key . '_' . $redirection->getId(), $route);
            }
        }
    }
}
