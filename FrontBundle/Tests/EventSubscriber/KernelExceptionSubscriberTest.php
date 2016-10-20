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
    protected $currentSiteManager;
    protected $site;
    protected $mainAlias;
    protected $nodeRepository;
    protected $templating;
    protected $requestStack;
    protected $request;
    protected $attributes;
    protected $event;
    protected $exception;

    protected $currentLanguage = 'en';
    protected $currentSiteId = 'siteId';
    protected $currentAliasId = 'aliasId';

    /**
     * Set up the test
     */
    public function setUp()
    {
        $this->mainAlias = Phake::mock('OpenOrchestra\ModelInterface\Model\SiteAliasInterface');
        Phake::when($this->mainAlias)->getLanguage()->thenReturn($this->currentLanguage);
        $this->site = Phake::mock('OpenOrchestra\ModelInterface\Model\ReadSiteInterface');
        Phake::when($this->site)->getSiteId()->thenReturn($this->currentSiteId);
        Phake::when($this->site)->getAliases()->thenReturn(array($this->currentAliasId => $this->mainAlias));
        $this->siteRepository = Phake::mock('OpenOrchestra\ModelInterface\Repository\ReadSiteRepositoryInterface');
        Phake::when($this->siteRepository)->findByAliasDomain(Phake::anyParameters())->thenReturn(array($this->site));

        $this->nodeRepository = Phake::mock('OpenOrchestra\ModelInterface\Repository\ReadNodeRepositoryInterface');
        $this->templating = Phake::mock('Symfony\Bundle\FrameworkBundle\Templating\EngineInterface');
        Phake::when($this->templating)->render(Phake::anyParameters())->thenReturn('404 html page');

        $this->request = Phake::mock('Symfony\Component\HttpFoundation\Request');
        $this->attributes = Phake::mock('Symfony\Component\HttpFoundation\ParameterBag');
        $this->request->attributes = $this->attributes;

        $this->requestStack = Phake::mock('Symfony\Component\HttpFoundation\RequestStack');
        Phake::when($this->requestStack)->getMasterRequest()->thenReturn($this->request);

        $this->exception = Phake::mock('Symfony\Component\HttpKernel\Exception\HttpException');
        $this->event = Phake::mock('Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent');
        Phake::when($this->event)->getException()->thenReturn($this->exception);

        $this->currentSiteManager = Phake::mock('OpenOrchestra\DisplayBundle\Manager\SiteManager');

        $this->subscriber = new KernelExceptionSubscriber(
            $this->siteRepository,
            $this->nodeRepository,
            $this->templating,
            $this->requestStack,
            $this->currentSiteManager
        );
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
     * @param int                    $expectedSiteFinding
     * 
     * @dataProvider getErrorContext
     */
    public function testOnKernelException($status, ReadNodeInterface $node = null, $expectedResponseCount, $expectedSiteFinding)
    {
        Phake::when($this->exception)->getStatusCode()->thenReturn($status);
        Phake::when($this->nodeRepository)->findOneCurrentlyPublished(Phake::anyParameters())->thenReturn($node);
        if ($expectedResponseCount) {
            Phake::when($this->currentSiteManager)->getCurrentSiteId()->thenReturn($this->currentSiteId);
            Phake::when($this->currentSiteManager)->getCurrentSiteDefaultLanguage()->thenReturn($this->currentLanguage);
        }

        $this->subscriber->onKernelException($this->event);

        Phake::verify($this->currentSiteManager, Phake::times($expectedSiteFinding))->setSiteId($this->currentSiteId);
        Phake::verify($this->currentSiteManager, Phake::times($expectedSiteFinding))->setCurrentLanguage($this->currentLanguage);
        Phake::verify($this->attributes, Phake::times($expectedSiteFinding))->set('siteId', $this->currentSiteId);
        Phake::verify($this->attributes, Phake::times($expectedSiteFinding))->set('_locale', $this->currentLanguage);
        Phake::verify($this->attributes, Phake::times($expectedSiteFinding))->set('aliasId', $this->currentAliasId);

        Phake::verify($this->event, Phake::times($expectedResponseCount))->setResponse(Phake::anyParameters());
    }

    /**
     * Provide error context
     */
    public function getErrorContext()
    {
        $node = Phake::mock('OpenOrchestra\ModelInterface\Model\ReadNodeInterface');

        return array(
            'Error 404 without 404 node' => array('404', null, 0, 1),
            'Error 404 with 404 node'    => array('404', $node, 1, 1),
            'Error 500 without 500 node' => array('500', null, 0, 0),
            'Error 500 with 500 node'    => array('500', $node, 0, 0),
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
            'Error 404' => array('404', true),
            'Error 500' => array('500', false),
        );
    }
}
