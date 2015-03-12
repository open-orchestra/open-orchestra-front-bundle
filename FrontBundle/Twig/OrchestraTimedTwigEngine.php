<?php

namespace OpenOrchestra\FrontBundle\Twig;

use Symfony\Bundle\TwigBundle\Debug\TimedTwigEngine;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Templating\TemplateNameParserInterface;
use Symfony\Component\Templating\TemplateReferenceInterface;

/**
 * Class OrchestraTimedTwigEngine
 */
class OrchestraTimedTwigEngine extends TimedTwigEngine
{
    use Renderable;

    protected $devices;

    /**
     * Constructor.
     *
     * @param \Twig_Environment           $environment A \Twig_Environment instance
     * @param TemplateNameParserInterface $parser      A TemplateNameParserInterface instance
     * @param FileLocatorInterface        $locator     A FileLocatorInterface instance
     * @param Stopwatch                   $stopwatch   A Stopwatch instance
     * @param RequestStack                $requestStack A RequestStack instance
     * @param array                       $devices
     */
    public function __construct(
        \Twig_Environment $environment,
        TemplateNameParserInterface $parser,
        FileLocatorInterface $locator,
        Stopwatch $stopwatch,
        RequestStack $requestStack,
        array $devices
    )
    {
        parent::__construct($environment, $parser, $locator, $stopwatch);

        $this->request = $requestStack->getMasterRequest();
        $this->devices = $devices;
    }
}
