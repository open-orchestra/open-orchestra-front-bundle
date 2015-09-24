<?php

namespace OpenOrchestra\FrontBundle\Routing;

use OpenOrchestra\ModelInterface\Model\ReadNodeInterface;
use OpenOrchestra\ModelInterface\Model\ReadRedirectionInterface;
use OpenOrchestra\ModelInterface\Model\ReadSiteAliasInterface;
use OpenOrchestra\ModelInterface\Model\ReadSiteInterface;
use OpenOrchestra\ModelInterface\Repository\ReadNodeRepositoryInterface;
use OpenOrchestra\ModelInterface\Repository\ReadSiteRepositoryInterface;
use OpenOrchestra\ModelInterface\Repository\RedirectionRepositoryInterface;
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
     * @param ReadNodeRepositoryInterface    $nodeRepository
     * @param ReadSiteRepositoryInterface    $siteRepository
     */
    public function __construct(RedirectionRepositoryInterface $redirectionRepository, ReadNodeRepositoryInterface $nodeRepository, ReadSiteRepositoryInterface $siteRepository)
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

        /** @var ReadRedirectionInterface $redirection */
        foreach ($redirections as $redirection) {
            $site = $this->siteRepository->findOneBySiteId($redirection->getSiteId());
            if ($redirection->getNodeId()) {
                /** @var ReadNodeInterface $node */
                $node = $this->nodeRepository->findOnePublishedByNodeIdAndLanguageAndSiteIdInLastVersion($redirection->getNodeId(), $redirection->getLocale(), $redirection->getSiteId());
                if ($node instanceof ReadNodeInterface) {
                    $parameterKey = 'route';
                    $nodeId = $node->getId();
                    $this->generateRouteForSite($site, $redirection, $parameterKey, $nodeId, null, $routes);
                }
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
     * @param ReadSiteInterface        $site
     * @param ReadRedirectionInterface $redirection
     * @param string               $parameterKey
     * @param string|null          $nodeId
     * @param string|null          $url
     * @param RouteCollection      $routes
     */
    protected function generateRouteForSite(ReadSiteInterface $site, ReadRedirectionInterface $redirection, $parameterKey, $nodeId = null, $url = null, RouteCollection $routes)
    {
        /** @var ReadSiteAliasInterface $alias */
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
