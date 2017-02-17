<?php

namespace OpenOrchestra\FrontBundle\Twig;

use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Templating\TemplateNameParserInterface;
use Symfony\Component\Config\FileLocatorInterface;

/**
 * Class OrchestraTwigEngine
 */
class OrchestraTwigEngine extends TwigEngine
{
    use Renderable;

    /**
     * Constructor.
     *
     * @param \Twig_Environment           $environment  A \Twig_Environment instance
     * @param TemplateNameParserInterface $parser       A TemplateNameParserInterface instance
     * @param FileLocatorInterface        $locator      A FileLocatorInterface instance
     * @param RequestStack                $requestStack A RequestStack instance
     * @param array                       $devices
     * @param string                      $deviceTypeField
     */
    public function __construct(
        \Twig_Environment $environment,
        TemplateNameParserInterface $parser,
        FileLocatorInterface $locator,
        RequestStack $requestStack,
        array $devices,
        $deviceTypeField
    )
    {
        parent::__construct($environment, $parser, $locator);

        $this->requestStack = $requestStack;
        $this->devices = $devices;
        $this->deviceTypeField = $deviceTypeField;
    }
}
