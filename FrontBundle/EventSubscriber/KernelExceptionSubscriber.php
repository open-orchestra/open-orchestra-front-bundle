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

    protected $currentSite;
    protected $currentLanguage;

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

            $this->setCurrentSiteInfo(
                trim($this->request->getHost(), '/'),
                trim($this->request->getPathInfo(), '/')
            );

            if ($this->currentSite && $this->currentLanguage && $html = $this->getCustom404Html()) {
                $event->setResponse(new Response($html, 404));
            }
        }
    }

    /**
     * Try to find and set the current site and language
     * 
     * @param string $host
     * @param string $path
     */
    private function setCurrentSiteInfo($host, $path)
    {
        $this->currentSite = null;
        $this->currentLanguage = null;
        $possibleSite = null;
        $possibleAlias = null;

        $matchingSites = $this->siteRepository->findByAliasDomain($host);

        /** @var ReadSiteInterface $site */
        foreach ($matchingSites as $site) {
            foreach ($site->getAliases() as $alias) {
                if ($alias->getPrefix() == '') {
                    $possibleSite = $site;
                    $possibleAlias = $alias;
                } else {
                    if ($host == $alias->getDomain() && strpos($path . '/', trim($alias->getPrefix(), '/') . '/') === 0) {
                        $this->currentSite = $site;
                        $this->currentLanguage = $alias->getLanguage();
                        return;
                    }
                }
            }
        }

        if ($possibleSite && $possibleAlias) {
            $this->currentSite = $possibleSite;
            $this->currentLanguage = $possibleAlias->getLanguage();
        }
    }

    /**
     * Get the 404 custom page for the current site / language if it has been contributed
     * 
     * @return string | null
     */
    private function getCustom404Html()
    {
        $siteId = $this->currentSite->getSiteId();
        $nodeId = ReadNodeInterface::ERROR_404_NODE_ID;
        $node = $this->nodeRepository->findOnePublishedByNodeIdAndLanguageAndSiteIdInLastVersion($nodeId, $this->currentLanguage, $siteId);

        if ($node) {
            return $this->templating->render(
                'OpenOrchestraFrontBundle:Node:show.html.twig',
                array(
                    'node' => $node,
                    'parameters' => array('siteId' => $node->getSiteId(), '_locale' => $node->getLanguage())
                )
            );
        } else {
            return null;
        }
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
