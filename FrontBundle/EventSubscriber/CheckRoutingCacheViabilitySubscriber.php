<?php

namespace OpenOrchestra\FrontBundle\EventSubscriber;

use OpenOrchestra\ModelInterface\Repository\ReadNodeRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Router;

/**
 * Class CheckRoutingCacheViabilitySubscriber
 */
class CheckRoutingCacheViabilitySubscriber implements EventSubscriberInterface
{
    protected $router;
    protected $nodeRepository;

    /**
     * @param Router                      $router
     * @param ReadNodeRepositoryInterface $nodeRepository
     */
    public function __construct(Router $router, ReadNodeRepositoryInterface $nodeRepository)
    {
        $this->router = $router;
        $this->nodeRepository = $nodeRepository;
    }

    /**
     * @param GetResponseForExceptionEvent $event
     */
    public function checkCacheFileAndRefresh(GetResponseForExceptionEvent $event)
    {
        if (
            ((!($exception = $event->getException()) instanceof NotFoundHttpException
            && ! $exception->getPrevious() instanceof ResourceNotFoundException)
            || $exception instanceof RouteNotFoundException
            || ! $event->isMasterRequest())
            && !($exception instanceof \Twig_Error_Runtime)
        ) {
            return;
        }

        $cacheDir = $this->router->getOption('cache_dir');
        $request = $event->getRequest();

        $matcherCacheClass = $cacheDir . '/' . $this->router->getOption('matcher_cache_class') . '.php';
        $warmupMatcher = $this->testCacheFile($matcherCacheClass);

        $generatorCacheClass = $cacheDir . '/' . $this->router->getOption('generator_cache_class') . '.php';
        $warmupGenerator = $this->testCacheFile($generatorCacheClass);

        if ($warmupMatcher || $warmupGenerator) {
            $this->router->warmUp($cacheDir);
        }

        $response = $event->getKernel()->handle($request, HttpKernelInterface::SUB_REQUEST);
        $event->setResponse($response);
        $event->stopPropagation();
    }

    /**
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::EXCEPTION => 'checkCacheFileAndRefresh',
        );
    }

    /**
     * @param string  $cacheClass
     *
     * @return bool
     */
    protected function testCacheFile($cacheClass)
    {
        if (file_exists($cacheClass)) {
            $cacheAge = filemtime($cacheClass);
            $lastPublishedNode = $this->nodeRepository->findLastPublished();
            if ($lastPublishedNode->getUpdatedAt() instanceof \DateTime && $cacheAge < $lastPublishedNode->getUpdatedAt()->getTimestamp()) {
                unlink($cacheClass);

                return true;
            }
        }

        return false;
    }
}
