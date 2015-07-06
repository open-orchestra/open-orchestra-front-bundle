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

    protected $node;
    protected $event;
    protected $router;
    protected $cacheDir;
    protected $nodeRepository;
    protected $matcherCacheClass;
    protected $generatorCacheClass;
    protected $fullMatcherCacheClass;
    protected $fullGeneratorCacheClass;

    /**
     * Set up the test
     */
    public function setUp()
    {
        $this->cacheDir = __DIR__ . '/cache';
        $this->matcherCacheClass = 'matcherCacheClass';
        $this->generatorCacheClass = 'generatorCacheClass';
        $this->fullMatcherCacheClass = $this->cacheDir . '/' . $this->matcherCacheClass . '.php';
        $this->fullGeneratorCacheClass = $this->cacheDir . '/' . $this->generatorCacheClass . '.php';

        $this->router = Phake::mock('OpenOrchestra\FrontBundle\Routing\OpenOrchestraRouter');
        Phake::when($this->router)->getOption('cache_dir')->thenReturn($this->cacheDir);
        Phake::when($this->router)->getOption('matcher_cache_class')->thenReturn($this->matcherCacheClass);
        Phake::when($this->router)->getOption('generator_cache_class')->thenReturn($this->generatorCacheClass);

        $this->node = Phake::mock('OpenOrchestra\ModelInterface\Model\ReadNodeInterface');
        $this->nodeRepository = Phake::mock('OpenOrchestra\ModelInterface\Repository\ReadNodeRepositoryInterface');
        Phake::when($this->nodeRepository)->findLastPublished(Phake::anyParameters())->thenReturn($this->node);

        $this->event = Phake::mock('Symfony\Component\HttpKernel\Event\GetResponseEvent');
        Phake::when($this->event)->isMasterRequest()->thenReturn(true);

        $this->subscriber = new CheckRoutingCacheViabilitySubscriber($this->router, $this->nodeRepository);
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
        $this->assertArrayHasKey(KernelEvents::REQUEST, $this->subscriber->getSubscribedEvents());
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
     * Test no interaction if request not master
     */
    public function testNoInteractionIfRequestNotMaster()
    {
        Phake::when($this->event)->isMasterRequest()->thenReturn(false);

        $this->subscriber->checkCacheFileAndRefresh($this->event);

        $this->assertNoInteractionWithMocks();
    }

    /**
     * Assert mocks have no interaction
     */
    protected function assertNoInteractionWithMocks()
    {
        Phake::verify($this->router, Phake::never())->warmUp(Phake::anyParameters());
        Phake::verify($this->nodeRepository, Phake::never())->warmUp(Phake::anyParameters());
    }
}
