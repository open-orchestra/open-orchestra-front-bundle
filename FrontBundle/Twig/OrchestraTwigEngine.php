<?php

namespace OpenOrchestra\FrontBundle\Twig;

use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Templating\TemplateNameParserInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Config\FileLocatorInterface;

/**
 * Class OrchestraTwigEngine
 */
class OrchestraTwigEngine extends TwigEngine
{
    use Renderable;

    protected $devices;

    /**
     * Constructor.
     *
     * @param \Twig_Environment           $environment  A \Twig_Environment instance
     * @param TemplateNameParserInterface $parser       A TemplateNameParserInterface instance
     * @param FileLocatorInterface        $locator      A FileLocatorInterface instance
     * @param RequestStack                $requestStack A RequestStack instance
     * @param array                       $devices
     */
    public function __construct(
        \Twig_Environment $environment,
        TemplateNameParserInterface $parser,
        FileLocatorInterface $locator,
        RequestStack $requestStack,
        array $devices
    )
    {
        parent::__construct($environment, $parser, $locator);

        $this->request = $requestStack->getMasterRequest();
        $this->devices = $devices;
    }
}
