<?php

namespace OpenOrchestra\FrontBundle\Tests\EventSubscriber;

use OpenOrchestra\BaseBundle\Tests\AbstractTest\AbstractBaseTestCase;
use OpenOrchestra\FrontBundle\EventSubscriber\KernelExceptionSubscriber;
use OpenOrchestra\ModelInterface\Model\ReadNodeInterface;
use Phake;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Test KernelExceptionSubscriber
 */
class KernelExceptionSubscriberTest extends AbstractBaseTestCase
{
    /**
     * @var KernelExceptionSubscriber
     */
    protected $subscriber;

    protected $siteRepository;
    protected $site;
    protected $mainAlias;
    protected $nodeRepository;
    protected $templating;
    protected $requestStack;
    protected $request;
    protected $event;
    protected $exception;

    /**
     * Set up the test
     */
    public function setUp()
    {
        $this->mainAlias = Phake::mock('OpenOrchestra\ModelInterface\Model\SiteAliasInterface');
        Phake::when($this->mainAlias)->getLanguage()->thenReturn('en');
        $this->site = Phake::mock('OpenOrchestra\ModelInterface\Model\ReadSiteInterface');
        Phake::when($this->site)->getAliases()->thenReturn(array($this->mainAlias));
        $this->siteRepository = Phake::mock('OpenOrchestra\ModelInterface\Repository\ReadSiteRepositoryInterface');
        Phake::when($this->siteRepository)->findByAliasDomain(Phake::anyParameters())->thenReturn(array($this->site));

        $this->nodeRepository = Phake::mock('OpenOrchestra\ModelInterface\Repository\ReadNodeRepositoryInterface');
        $this->templating = Phake::mock('Symfony\Bundle\FrameworkBundle\Templating\EngineInterface');
        Phake::when($this->templating)->render(Phake::anyParameters())->thenReturn('404 html page');

        $this->request = Phake::mock('Symfony\Component\HttpFoundation\Request');
        $this->requestStack = Phake::mock('Symfony\Component\HttpFoundation\RequestStack');
        Phake::when($this->requestStack)->getMasterRequest()->thenReturn($this->request);

        $this->exception = Phake::mock('Symfony\Component\HttpKernel\Exception\HttpException');
        $this->event = Phake::mock('Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent');
        Phake::when($this->event)->getException()->thenReturn($this->exception);

        $this->subscriber = new KernelExceptionSubscriber($this->siteRepository, $this->nodeRepository, $this->templating, $this->requestStack);
    }

    /**
     * Test instance
     */
    public function testInstance()
    {
        $this->assertInstanceOf('Symfony\Component\EventDispatcher\EventSubscriberInterface', $this->subscriber);
    }

    /**
     * Test subscribed events
     */
    public function testSubscribedEvent()
    {
        $this->assertArrayHasKey(KernelEvents::EXCEPTION, $this->subscriber->getSubscribedEvents());
        $this->assertTrue(method_exists($this->subscriber, 'onKernelException'));
    }

    /**
     * @param string                 $status
     * @param ReadNodeInterface|null $node
     * @param int                    $expectedResponseCount
     * 
     * @dataProvider getErrorContext
     */
    public function testOnKernelException($status, ReadNodeInterface $node = null, $expectedResponseCount)
    {
        Phake::when($this->exception)->getStatusCode()->thenReturn($status);
        Phake::when($this->nodeRepository)->findPublishedInLastVersion(Phake::anyParameters())->thenReturn($node);

        $this->subscriber->onKernelException($this->event);

        Phake::verify($this->event, Phake::times($expectedResponseCount))->setResponse(Phake::anyParameters());
    }

    /**
     * Provide error context
     */
    public function getErrorContext()
    {
        $node = Phake::mock('OpenOrchestra\ModelInterface\Model\ReadNodeInterface');

        return array(
            array('404', null, 0),
            array('404', $node, 1),
            array('500', null, 0),
            array('500', $node, 0),
        );
    }

    /**
     * @param string $status
     * @param bool   $expectedException
     * 
     * @dataProvider getErrorContextWithException
     */
    public function testOnKernelExceptionWithNoSite($status, $expectedException)
    {
        Phake::when($this->exception)->getStatusCode()->thenReturn($status);
        Phake::when($this->siteRepository)->findByAliasDomain(Phake::anyParameters())->thenReturn(array());

        if ($expectedException) {
            $this->setExpectedException('OpenOrchestra\FrontBundle\Exception\NonExistingSiteException');
        }

        $this->subscriber->onKernelException($this->event);
    }

    /**
     * Provide error context
     */
    public function getErrorContextWithException()
    {
        $node = Phake::mock('OpenOrchestra\ModelInterface\Model\ReadNodeInterface');

        return array(
            array('404', true),
            array('500', false),
        );
    }
}
