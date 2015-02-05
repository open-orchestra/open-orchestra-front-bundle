<?php

namespace PHPOrchestra\FrontBundle\Test\Routing;

use Doctrine\Common\Collections\ArrayCollection;
use Phake;
use PHPOrchestra\FrontBundle\Routing\DatabaseRouteLoader;

/**
 * Test DatabaseRouteLoaderTest
 */
class DatabaseRouteLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DatabaseRouteLoader
     */
    protected $loader;

    protected $resource = '.';
    protected $nodeRepository;

    /**
     * Set up the test
     */
    public function setUp()
    {
        $this->nodeRepository = Phake::mock('PHPOrchestra\ModelInterface\Repository\NodeRepositoryInterface');
        Phake::when($this->nodeRepository)->findByNodeType()->thenReturn(array());

        $this->loader = new DatabaseRouteLoader($this->nodeRepository);
    }

    /**
     * Test instance
     */
    public function testInstance()
    {
        $this->assertInstanceOf('Symfony\Component\Config\Loader\LoaderInterface', $this->loader);
    }

    /**
     * Test support
     */
    public function testSupport()
    {

        $this->assertTrue($this->loader->supports($this->resource, 'database'));
    }

    /**
     * test exception
     */
    public function testRunOnlyOnce()
    {
        $this->loader->load($this->resource, 'database');
        $this->setExpectedException('RuntimeException');
        $this->loader->load($this->resource, 'database');
    }

    /**
     * Test load routes
     */
    public function testLoad()
    {
        $nodeId = 'nodeId';
        $mongoId = 'mongoId';
        $pattern = '/nodeId/{variable}';
        $node = Phake::mock('PHPOrchestra\ModelInterface\Model\NodeInterface');
        Phake::when($node)->getId()->thenReturn($mongoId);
        Phake::when($node)->getNodeId()->thenReturn($nodeId);
        Phake::when($node)->getRoutePattern()->thenReturn($pattern);
        $nodes = new ArrayCollection();
        $nodes->add($node);

        Phake::when($this->nodeRepository)->findByNodeType()->thenReturn($nodes);

        $routeCollection = $this->loader->load($this->resource, 'database');

        $this->assertInstanceOf('Symfony\Component\Routing\RouteCollection', $routeCollection);
        $this->assertCount(1, $routeCollection);
        $route = $routeCollection->get($mongoId);
        $this->assertInstanceOf('Symfony\Component\Routing\Route', $route);
        $this->assertSame($pattern, $route->getPath());
        $this->assertSame(
            array(
                '_controller' => 'PHPOrchestra\FrontBundle\Controller\NodeController::showAction',
                'nodeId' => $nodeId,
            ),
            $route->getDefaults());
    }
}
