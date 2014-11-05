<?php

namespace PHPOrchestra\FrontBundle\Test\EventSubscriber;

use Phake;
use PHPOrchestra\FrontBundle\EventSubscriber\DynamicRoutingSubscriber;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * Class DynamicRoutingSubscriberTest
 */
class DynamicRoutingSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DynamicRoutingSubscriber
     */
    protected $subscriber;

    protected $event;
    protected $kernel;
    protected $headers;
    protected $request;
    protected $response;
    protected $exception;
    protected $attributes;
    protected $previousException;
    protected $dynamicRoutingManager;
    protected $pathInfo = '/pathInfo';

    /**
     * Set up the test
     */
    public function setUp()
    {
        $this->attributes = array('_controller' => 'NodeController');

        $this->dynamicRoutingManager = Phake::mock('PHPOrchestra\FrontBundle\Manager\DynamicRoutingManager');
        Phake::when($this->dynamicRoutingManager)
            ->getRouteParameterFromRequestPathInfo(Phake::anyParameters())
            ->thenReturn($this->attributes);

        $this->response = new \Symfony\Component\HttpFoundation\Response('', 200, array());

        $this->kernel = Phake::mock('Symfony\Component\HttpKernel\Kernel');
        Phake::when($this->kernel)->handle(Phake::anyParameters())->thenReturn($this->response);

        $this->request = Phake::mock('Symfony\Component\HttpFoundation\Request');
        Phake::when($this->request)->getPathInfo()->thenReturn($this->pathInfo);
        Phake::when($this->request)->duplicate(Phake::anyParameters())->thenReturn($this->request);

        $this->previousException = new ResourceNotFoundException();
        $this->exception = new NotFoundHttpException(null, $this->previousException);

        $this->event = Phake::mock('Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent');
        Phake::when($this->event)->getException()->thenReturn($this->exception);
        Phake::when($this->event)->getKernel()->thenReturn($this->kernel);
        Phake::when($this->event)->getRequest()->thenReturn($this->request);

        $this->subscriber = new DynamicRoutingSubscriber($this->dynamicRoutingManager);
    }

    /**
     * Test instance
     */
    public function testInstance()
    {
        $this->assertInstanceOf('Symfony\Component\EventDispatcher\EventSubscriberInterface', $this->subscriber);
    }

    /**
     * Test event subscribed
     */
    public function testEventSubscribed()
    {
        $this->assertArrayHasKey(KernelEvents::EXCEPTION, $this->subscriber->getSubscribedEvents());
    }

    public function testWithException()
    {
        $this->subscriber->onKernelException($this->event);

        Phake::verify($this->event)->getException();
        Phake::verify($this->event)->getRequest();
        Phake::verify($this->dynamicRoutingManager)->getRouteParameterFromRequestPathInfo($this->pathInfo);
        Phake::verify($this->request)->duplicate(null, null, $this->attributes);
        Phake::verify($this->kernel)->handle($this->request, HttpKernelInterface::MASTER_REQUEST, true);
        Phake::verify($this->event)->setResponse($this->response);
        Phake::verify($this->event)->stopPropagation();
        $this->assertTrue($this->response->headers->has('X-Status-Code'));
        $this->assertSame(200, $this->response->headers->get('X-Status-Code'));
    }
}
