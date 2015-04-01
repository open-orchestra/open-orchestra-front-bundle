<?php

namespace OpenOrchestra\FrontBundle\Tests\EventSubscriber;

use OpenOrchestra\FrontBundle\EventSubscriber\CheckRoutingCacheViabilitySubscriber;
use Phake;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Test CheckRoutingCacheViabilitySubscriberTest
 */
class CheckRoutingCacheViabilitySubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CheckRoutingCacheViabilitySubscriber
     */
    protected $subscriber;

    protected $site;
    protected $node;
    protected $host;
    protected $event;
    protected $router;
    protected $kernel;
    protected $request;
    protected $cacheDir;
    protected $nodeRepository;
    protected $siteRepository;
    protected $matcherCacheClass;
    protected $generatorCacheClass;
    protected $fullMatcherCacheClass;
    protected $fullGeneratorCacheClass;

    /**
     * Set up the test
     */
    public function setUp()
    {
        $this->host = 'host';
        $this->cacheDir = __DIR__ . '/cache';
        $this->matcherCacheClass = 'matcherCacheClass';
        $this->generatorCacheClass = 'generatorCacheClass';
        $this->fullMatcherCacheClass = $this->cacheDir . '/' . $this->matcherCacheClass . '.php';
        $this->fullGeneratorCacheClass = $this->cacheDir . '/' . $this->generatorCacheClass . '.php';

        $this->router = Phake::mock('OpenOrchestra\FrontBundle\Routing\OpenOrchestraRouter');
        Phake::when($this->router)->getOption('cache_dir')->thenReturn($this->cacheDir);
        Phake::when($this->router)->getOption('matcher_cache_class')->thenReturn($this->matcherCacheClass);
        Phake::when($this->router)->getOption('generator_cache_class')->thenReturn($this->generatorCacheClass);

        $this->site = Phake::mock('OpenOrchestra\ModelInterface\Model\ReadSiteInterface');
        $this->siteRepository = Phake::mock('OpenOrchestra\ModelInterface\Repository\SiteRepositoryInterface');
        Phake::when($this->siteRepository)->findByAliasDomain(Phake::anyParameters())->thenReturn($this->site);

        $this->node = Phake::mock('OpenOrchestra\ModelInterface\Model\ReadNodeInterface');
        $this->nodeRepository = Phake::mock('OpenOrchestra\ModelInterface\Repository\ReadNodeRepositoryInterface');
        Phake::when($this->nodeRepository)->findLastPublished(Phake::anyParameters())->thenReturn($this->node);

        $response = Phake::mock('Symfony\Component\HttpFoundation\Response');
        $this->kernel = Phake::mock('Symfony\Component\HttpKernel\Kernel');
        Phake::when($this->kernel)->handle(Phake::anyParameters())->thenReturn($response);
        $this->request = Phake::mock('Symfony\Component\HttpFoundation\Request');
        Phake::when($this->request)->getHost()->thenReturn($this->host);
        $this->event = Phake::mock('Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent');
        $this->generateException();
        Phake::when($this->event)->getRequest()->thenReturn($this->request);
        Phake::when($this->event)->getKernel()->thenReturn($this->kernel);

        $this->subscriber = new CheckRoutingCacheViabilitySubscriber($this->router, $this->nodeRepository, $this->siteRepository);
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
    }

    /**
     * Test check cache file
     */
    public function testCheckCacheFileAndRefreshWhenFileOutdated()
    {
        $this->createFiles();

        $nodeDate = new \DateTime();
        $nodeDate->add(new \DateInterval('P1M'));
        Phake::when($this->node)->getUpdatedAt()->thenReturn($nodeDate);

        $this->subscriber->checkCacheFileAndRefresh($this->event);

        $this->assertFileNotExists($this->fullMatcherCacheClass);
        $this->assertFileNotExists($this->fullGeneratorCacheClass);
        Phake::verify($this->router)->warmUp(Phake::anyParameters());
        Phake::verify($this->event)->setResponse(Phake::anyParameters());
        Phake::verify($this->event)->stopPropagation();
    }

    /**
     * Test check cache file
     */
    public function testCheckCacheFileAndRefreshWhenFileOndated()
    {
        $this->createFiles();

        $nodeDate = new \DateTime();
        $nodeDate->sub(new \DateInterval('P1M'));
        Phake::when($this->node)->getUpdatedAt()->thenReturn($nodeDate);

        $this->subscriber->checkCacheFileAndRefresh($this->event);

        $this->assertFileExists($this->fullMatcherCacheClass);
        $this->assertFileExists($this->fullGeneratorCacheClass);
        Phake::verify($this->router, Phake::never())->warmUp(Phake::anyParameters());
        Phake::verify($this->event)->setResponse(Phake::anyParameters());
        Phake::verify($this->event)->stopPropagation();
    }

    /**
     * Create all cache files
     */
    protected function createFiles()
    {
        if (file_exists($this->fullGeneratorCacheClass)) {
            unlink($this->fullGeneratorCacheClass);
        }
        touch($this->fullGeneratorCacheClass);
        if (file_exists($this->fullMatcherCacheClass)) {
            unlink($this->fullMatcherCacheClass);
        }
        touch($this->fullMatcherCacheClass);
    }

    /**
     * Generate all the exceptions thrown
     */
    protected function generateException()
    {
        $previous = Phake::mock('Symfony\Component\Routing\Exception\ResourceNotFoundException');
        $exception = Phake::mock('Symfony\Component\HttpKernel\Exception\NotFoundHttpException');
        Phake::when($exception)->getPrevious()->thenReturn($previous);
        Phake::when($this->event)->getException()->thenReturn($exception);
        Phake::when($this->event)->isMasterRequest()->thenReturn(true);
    }

    /**
     * Test no interaction if request not master
     */
    public function testNoInteractionIfRequestNotMaster()
    {
        Phake::when($this->event)->isMasterRequest()->thenReturn(false);

        $this->subscriber->checkCacheFileAndRefresh($this->event);

        $this->assertNoInteractionWithMocks();
    }

    /**
     * Test no interaction when exception not right
     */
    public function testNoInteractionIfNotRightException()
    {
        Phake::when($this->event)->getException()->thenReturn(new \Exception());

        $this->subscriber->checkCacheFileAndRefresh($this->event);

        $this->assertNoInteractionWithMocks();
    }

    /**
     * Assert mocks have no interaction
     */
    protected function assertNoInteractionWithMocks()
    {
        Phake::verify($this->router, Phake::never())->warmUp(Phake::anyParameters());
        Phake::verify($this->event, Phake::never())->setResponse(Phake::anyParameters());
        Phake::verify($this->event, Phake::never())->stopPropagation();
    }
}
