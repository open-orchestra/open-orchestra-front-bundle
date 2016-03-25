<?php

namespace OpenOrchestra\FrontBundle\Routing;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Routing\Generator\ConfigurableRequirementsInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\DependencyInjection\ContainerInterface;
use OpenOrchestra\ModelInterface\Repository\ReadSiteRepositoryInterface;
use OpenOrchestra\BaseBundle\Context\CurrentSiteIdInterface;

/**
 * The FrameworkBundle router is extended to inject documents service
 * in OpenOrchestraUrlMatcher
 */
class OpenOrchestraRouter extends Router
{
    protected $siteRepository;
    protected $currentSiteManager;
    protected $requestStack;
    protected $nodeManager;

    /**
     * Extends parent constructor to get documents service
     * as $container is private in parent class
     *
     * @param ReadSiteRepositoryInterface $siteRepository
     * @param CurrentSiteIdInterface      $currentSiteManager
     * @param ContainerInterface          $container
     * @param mixed                       $resource
     * @param array                       $options
     * @param RequestContext              $context
     */
    public function __construct(
        ReadSiteRepositoryInterface $siteRepository,
        CurrentSiteIdInterface $currentSiteManager,
        ContainerInterface $container,
        $resource,
        array $options = array(),
        RequestContext $context = null
    )
    {
        parent::__construct($container, $resource, $options, $context);

        $this->siteRepository = $siteRepository;
        $this->currentSiteManager = $currentSiteManager;
        $this->requestStack = $container->get('request_stack');
        $this->nodeManager = $container->get('open_orchestra_front.manager.node');
    }

    /**
     * Gets the UrlGenerator instance associated with this Router.
     *
     * @return UrlGeneratorInterface A UrlGeneratorInterface instance
     */
    public function getGenerator()
    {
        if (null !== $this->generator) {
            return $this->generator;
        }

        if (null === $this->options['cache_dir'] || null === $this->options['generator_cache_class']) {
            $this->generator =  new $this->options['generator_class'](
                    $this->siteRepository,
                    $this->currentSiteManager,
                    $this->getRouteCollection(),
                    $this->context,
                    $this->requestStack,
                    $this->nodeManager,
                    $this->logger
                );
        } else {
            $class = $this->options['generator_cache_class'];
            $cache = new ConfigCache($this->options['cache_dir'].'/'.$class.'.php', $this->options['debug']);
            if (!$cache->isFresh()) {
                $dumper = $this->getGeneratorDumperInstance();

                $options = array(
                    'class' => $class,
                    'base_class' => $this->options['generator_base_class'],
                );

                $cache->write($dumper->dump($options), $this->getRouteCollection()->getResources());
            }

            require_once $cache->getPath();

            $this->generator = new $class($this->context, $this->requestStack, $this->nodeManager, $this->logger);
        }

        if ($this->generator instanceof ConfigurableRequirementsInterface) {
            $this->generator->setStrictRequirements($this->options['strict_requirements']);
        }

        return $this->generator;
    }
}
