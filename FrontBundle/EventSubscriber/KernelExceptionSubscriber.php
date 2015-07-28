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
            $currentSite = $this->siteRepository->findByAliasDomain($this->request->getHost());

            if ($currentSite) {
                $language = $this->getLanguage($currentSite);
                $siteId = $currentSite->getSiteId();
                $nodeId = ReadNodeInterface::ERROR_404_NODE_ID;
                $node = $this->nodeRepository->findOnePublishedByNodeIdAndLanguageAndSiteIdInLastVersion($nodeId, $language, $siteId);

                if ($node) {
                    $html = $this->templating->render(
                        'OpenOrchestraFrontBundle:Node:show.html.twig',
                        array(
                            'node' => $node,
                            'parameters' => array('siteId' => $node->getSiteId(), 'language' => $node->getLanguage())
                        )
                    );
                    $event->setResponse(new Response($html, 404));
                }
            }
        }
    }

    /**
     * Get the language to display the 404 page for $site
     * If a language prefix maps the url, get the corresponding language
     * Else get the language of the main alias
     * 
     * @param ReadSiteInterface $site
     * 
     * @return string
     */
    protected function getLanguage(ReadSiteInterface $site)
    {
        $targetAlias = null;
        $path = trim($this->request->getPathInfo(), '/');
        $host = $this->request->getHost();

        if ('' != $path) {
            foreach ($site->getAliases() as $alias) {
                if ($host == $alias->getDomain() && strpos($path . '/', $alias->getPrefix() . '/') === 0) {
                    $targetAlias = $alias;
                    break;
                }
            }
        }

        if (!$targetAlias) {
            $targetAlias = $site->getMainAlias();
        }

        return $targetAlias->getLanguage();
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
