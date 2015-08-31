<?php

namespace OpenOrchestra\FrontBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use OpenOrchestra\ModelInterface\Repository\SiteRepositoryInterface;
use OpenOrchestra\ModelInterface\Repository\ReadNodeRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use OpenOrchestra\ModelInterface\Model\ReadSiteInterface;
use OpenOrchestra\ModelInterface\Model\ReadNodeInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Class KernelExceptionSubscriber
 */
class KernelExceptionSubscriber implements EventSubscriberInterface
{
    protected $siteRepository;
    protected $nodeRepository;
    protected $templating;
    protected $request;

    /**
     * @param SiteRepositoryInterface     $siteRepository
     * @param ReadNodeRepositoryInterface $nodeRepository
     * @param EngineInterface             $templating
     * @param RequestStack                $requestStack
     */
    public function __construct(
        SiteRepositoryInterface $siteRepository,
        ReadNodeRepositoryInterface $nodeRepository,
        EngineInterface $templating,
        RequestStack $requestStack
    ) {
        $this->siteRepository = $siteRepository;
        $this->nodeRepository = $nodeRepository;
        $this->templating = $templating;
        $this->request = $requestStack->getMasterRequest();
    }

    /**
     * If exception type is 404, display the Orchestra 404 node instead of Symfony exception
     * 
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if ($event->getException() instanceof HttpExceptionInterface && '404' == $event->getException()->getStatusCode()) {

            $siteInfo = $this->getCurrentSiteInfo(
                trim($this->request->getHost(), '/'),
                trim($this->request->getPathInfo(), '/')
            );

            if ($html = $this->getCustom404Html($siteInfo['site'], $siteInfo['language'])) {
                $event->setResponse(new Response($html, 404));
            }
        }
    }

    /**
     * Try to find and set the current site and language
     * 
     * @param string $host
     * @param string $path
     * 
     * @return array
     */
    protected function getCurrentSiteInfo($host, $path)
    {
        $path = $this->formatPath($path);
        $possibleSite = null;
        $possibleAlias = null;
        $matchingLength = -1;

        $matchingSites = $this->siteRepository->findByAliasDomain($host);

        /** @var ReadSiteInterface $site */
        foreach ($matchingSites as $site) {
            foreach ($site->getAliases() as $alias) {
                $aliasPrefix = $this->formatPath($alias->getPrefix());
                if ($host == $alias->getDomain() && strpos($path, $aliasPrefix) === 0) {
                    $splitLength = count(explode('/', $aliasPrefix));
                    if ($splitLength > $matchingLength) {
                        $possibleAlias = $alias;
                        $possibleSite = $site;
                        $matchingLength = $splitLength;
                    }
                }
            }
        }

        return array(
            'site' => $possibleSite,
            'language' => $possibleAlias->getLanguage()
        );
    }

    /**
     * Format a path to be compared with another formatted path
     * Ouptut a path formatted like this : /lvl1/lvl2/lvl3/ (/ if no path)
     * 
     * @param string $path
     * 
     * @return string
     */
    protected function formatPath($path)
    {
        $path = trim($path, '/') . '/';
        if (strlen($path) > 1) {
            $path = '/' . $path;
        }

        return $path;
    }

    /**
     * Get the 404 custom page for the current site / language if it has been contributed
     * 
     * @param ReadSiteInterface $site
     * @param string            $language
     * 
     * @return string | null
     */
    protected function getCustom404Html(ReadSiteInterface $site, $language)
    {
        if (!$site || !$language) {
            return null;
        }

        $siteId = $site->getSiteId();
        $nodeId = ReadNodeInterface::ERROR_404_NODE_ID;
        $node = $this->nodeRepository->findOnePublishedByNodeIdAndLanguageAndSiteIdInLastVersion($nodeId, $language, $siteId);

        if ($node) {
            return $this->templating->render(
                'OpenOrchestraFrontBundle:Node:show.html.twig',
                array(
                    'node' => $node,
                    'parameters' => array('siteId' => $node->getSiteId(), '_locale' => $node->getLanguage())
                )
            );
        }

        return null;
    }

    /**
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::EXCEPTION => array('onKernelException', 50)
        );
    }
}
