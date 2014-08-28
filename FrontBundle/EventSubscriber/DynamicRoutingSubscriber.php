<?php

namespace PHPOrchestra\FrontBundle\EventSubscriber;

use PHPOrchestra\FrontBundle\Manager\DynamicRoutingManager;
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
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if (!($exception = $event->getException()->getPrevious()) instanceof ResourceNotFoundException) {
            return;
        }

        $request = $event->getRequest();

        $attributes = $this->dynamicRoutingManager->getRouteParameterFromRequestPathInfo($request->getPathInfo());

        $request = $request->duplicate(null, null, $attributes);
        $request->setMethod('GET');

        $response = $event->getKernel()->handle($request, HttpKernelInterface::SUB_REQUEST, true);
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
