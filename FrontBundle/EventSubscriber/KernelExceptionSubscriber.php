<?php

namespace OpenOrchestra\FrontBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use OpenOrchestra\ModelInterface\Repository\ReadSiteRepositoryInterface;
use OpenOrchestra\ModelInterface\Repository\ReadNodeRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use OpenOrchestra\ModelInterface\Model\ReadSiteInterface;
use OpenOrchestra\ModelInterface\Model\ReadNodeInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use OpenOrchestra\FrontBundle\Exception\NonExistingSiteException;
use OpenOrchestra\DisplayBundle\Manager\SiteManager;

/**
 * Class KernelExceptionSubscriber
 */
class KernelExceptionSubscriber implements EventSubscriberInterface
{
    protected $siteRepository;
    protected $nodeRepository;
    protected $templating;
    protected $request;
    protected $currentSiteManager;

    /**
     * @param ReadSiteRepositoryInterface $siteRepository
     * @param ReadNodeRepositoryInterface $nodeRepository
     * @param EngineInterface             $templating
     * @param RequestStack                $requestStack
     * @param SiteManager                 $currentSiteManager
     */
    public function __construct(
        ReadSiteRepositoryInterface $siteRepository,
        ReadNodeRepositoryInterface $nodeRepository,
        EngineInterface $templating,
        RequestStack $requestStack,
        SiteManager $currentSiteManager
    ) {
        $this->siteRepository = $siteRepository;
        $this->nodeRepository = $nodeRepository;
        $this->templating = $templating;
        $this->request = $requestStack->getMasterRequest();
        $this->currentSiteManager = $currentSiteManager;
    }

    /**
     * If exception type is 404, display the Orchestra 404 node instead of Symfony exception
     * 
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if ($event->getException() instanceof HttpExceptionInterface && '404' == $event->getException()->getStatusCode()) {

            $this->getCurrentSiteInfo(trim($this->request->getHost(), '/'), trim($this->request->getPathInfo(), '/'));

            if ($html = $this->getCustom404Html()) {
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
     * @throws NonExistingSiteException
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

        if (is_null($possibleAlias)) {
            throw new NonExistingSiteException();
        }

        $this->currentSiteManager->setSiteId($possibleSite->getSiteId());
        $this->currentSiteManager->setCurrentLanguage($possibleAlias->getLanguage());
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
     * @return string | null
     */
    protected function getCustom404Html()
    {
        if (!$this->currentSiteManager->getCurrentSiteId() || !$this->currentSiteManager->getCurrentSiteDefaultLanguage()) {
            return null;
        }

        $nodeId = ReadNodeInterface::ERROR_404_NODE_ID;
        $node = $this->nodeRepository->findOneCurrentlyPublished(
            $nodeId,
            $this->currentSiteManager->getCurrentSiteDefaultLanguage(),
            $this->currentSiteManager->getCurrentSiteId()
        );

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
