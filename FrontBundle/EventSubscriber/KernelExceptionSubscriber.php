<?php

namespace OpenOrchestra\FrontBundle\EventSubscriber;

use OpenOrchestra\DisplayBundle\Manager\ContextInterface;
use OpenOrchestra\FrontBundle\Exception\DisplayBlockException;
use OpenOrchestra\FrontBundle\Manager\TemplateManager;
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
    protected $templateManager;

    /**
     * @param ReadSiteRepositoryInterface $siteRepository
     * @param ReadNodeRepositoryInterface $nodeRepository
     * @param EngineInterface             $templating
     * @param RequestStack                $requestStack
     * @param ContextInterface            $currentSiteManager
     * @param TemplateManager             $templateManager
     */
    public function __construct(
        ReadSiteRepositoryInterface $siteRepository,
        ReadNodeRepositoryInterface $nodeRepository,
        EngineInterface $templating,
        RequestStack $requestStack,
        ContextInterface $currentSiteManager,
        TemplateManager $templateManager
    ) {
        $this->siteRepository = $siteRepository;
        $this->nodeRepository = $nodeRepository;
        $this->templating = $templating;
        $this->request = $requestStack->getMasterRequest();
        $this->currentSiteManager = $currentSiteManager;
        $this->templateManager = $templateManager;
    }

    /**
     * If exception type is 404, display the Orchestra 404 node instead of Symfony exception
     * 
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if ($event->getException() instanceof DisplayBlockException) {
            $event->getRequest()->setRequestFormat('fragment.'. $event->getRequest()->getRequestFormat());
            $event->setException($event->getException()->getPrevious());
        } elseif ($event->getException() instanceof HttpExceptionInterface && '404' == $event->getException()->getStatusCode()) {
            $this->setCurrentSiteInfo(trim($this->request->getHost(), '/'), trim($this->request->getPathInfo(), '/'));

            if ($html = $this->getCustom404Html()) {
                $event->setResponse(new Response($html, 404));
            }
        }
    }

    /**
     * Find and set the current site id, language, and alias id
     * 
     * @param string $host
     * @param string $path
     * 
     * @throws NonExistingSiteException
     */
    protected function setCurrentSiteInfo($host, $path)
    {
        $path = $this->formatPath($path);
        $currentSiteId = null;
        $currentLanguage = null;
        $currentAliasId = null;
        $matchingLength = -1;

        $matchingSites = $this->siteRepository->findByAliasDomain($host);

        /** @var ReadSiteInterface $site */
        foreach ($matchingSites as $site) {
            foreach ($site->getAliases() as $aliasId => $alias) {
                $aliasPrefix = $this->formatPath($alias->getPrefix());
                if ($host == $alias->getDomain() && strpos($path, $aliasPrefix) === 0) {
                    $splitLength = count(explode('/', $aliasPrefix));
                    if ($splitLength > $matchingLength) {
                        $currentSiteId = $site->getSiteId();
                        $currentLanguage = $alias->getLanguage();
                        $currentAliasId = $aliasId;
                        $matchingLength = $splitLength;
                    }
                }
            }
        }

        if (is_null($currentSiteId)) {
            throw new NonExistingSiteException();
        }

        $this->currentSiteManager->setSiteId($currentSiteId);
        $this->currentSiteManager->setLanguage($currentLanguage);

        $this->request->attributes->set('siteId', $currentSiteId);
        $this->request->attributes->set('_locale', $currentLanguage);
        $this->request->attributes->set('aliasId', $currentAliasId);
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
        if (!$this->currentSiteManager->getSiteId() || !$this->currentSiteManager->getSiteLanguage()) {
            return null;
        }

        $nodeId = ReadNodeInterface::ERROR_404_NODE_ID;
        $node = $this->nodeRepository->findOnePublished(
            $nodeId,
            $this->currentSiteManager->getSiteLanguage(),
            $this->currentSiteManager->getSiteId()
        );

        if ($node) {
            $site = $this->siteRepository->findOneBySiteId($node->getSiteId());

            return $this->templating->render(
                $this->getTemplate($node),
                array(
                    'node' => $node,
                    'site' => $site,
                    'parameters' => array('siteId' => $node->getSiteId(), '_locale' => $node->getLanguage())
                )
            );
        }

        return null;
    }

    /**
     * @param ReadNodeInterface $node
     *
     * @return string
     * @throws \OpenOrchestra\FrontBundle\Exception\NonExistingTemplateException
     */
    protected function getTemplate(ReadNodeInterface $node) {
        $site = $this->siteRepository->findOneBySiteId($node->getSiteId());

        $template = $node->getTemplate();
        $templateSet = $site->getTemplateSet();

        return $this->templateManager->getTemplate($template, $templateSet);
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
