<?php

namespace OpenOrchestra\FrontBundle\Routing;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Routing\Generator\ConfigurableRequirementsInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The FrameworkBundle router is extended to inject documents service
 * in OpenOrchestraUrlMatcher
 */
class OpenOrchestraRouter extends Router
{
    protected $requestStack;

    /**
     * Extends parent constructor to get documents service
     * as $container is private in parent class
     *
     * @param ContainerInterface $container
     * @param mixed              $resource
     * @param array              $options
     * @param RequestContext     $context
     */
    public function __construct(
        ContainerInterface $container,
        $resource,
        array $options = array(),
        RequestContext $context = null
    )
    {
        parent::__construct($container, $resource, $options, $context);

        $this->requestStack = $container->get('request_stack');
    }

    /**
     * Gets the UrlGenerator instance associated with this Router.
     *
     * @return UrlGeneratorInterface A UrlGeneratorInterface instance
     */
    public function getGenerator()
    {
        if (null === $this->options['cache_dir'] || null === $this->options['generator_cache_class']) {
            $this->generator =  new $this->options['generator_class'](
                    $this->getRouteCollection(),
                    $this->context,
                    $this->requestStack,
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

            require_once $cache;

            $this->generator = new $class($this->context, $this->requestStack, $this->logger);
        }

        if ($this->generator instanceof ConfigurableRequirementsInterface) {
            $this->generator->setStrictRequirements($this->options['strict_requirements']);
        }

        return $this->generator;
    }
}
