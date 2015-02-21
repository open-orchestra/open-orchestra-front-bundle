<?php

namespace OpenOrchestra\FrontBundle\EventSubscriber;

use OpenOrchestra\FrontBundle\Exception\DynamicRoutingUsedException;
use OpenOrchestra\FrontBundle\Manager\DynamicRoutingManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * Class DynamicRoutingSubscriber
 */
class DynamicRoutingSubscriber implements EventSubscriberInterface
{
    protected $dynamicRoutingManager;

    /**
     * @param DynamicRoutingManager $dynamicRoutingManager
     */
    public function __construct(DynamicRoutingManager $dynamicRoutingManager)
    {
        $this->dynamicRoutingManager = $dynamicRoutingManager;
    }

    /**
     * @param GetResponseForExceptionEvent $event
     *
     * @deprecated use dynamic routing
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if (!($exception = $event->getException()->getPrevious()) instanceof ResourceNotFoundException) {
            return;
        }

        $request = $event->getRequest();

        $attributes = $this->dynamicRoutingManager->getRouteParameterFromRequestPathInfo($request->getPathInfo());

        $request = $request->duplicate(null, null, $attributes);

        $response = $event->getKernel()->handle($request, HttpKernelInterface::MASTER_REQUEST, true);
//        Change the returned status
        $response->headers->set('X-status-Code', 200);
        $event->setResponse($response);
        $event->stopPropagation();
    }

    /**
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::EXCEPTION => 'onKernelException'
        );
    }

}
