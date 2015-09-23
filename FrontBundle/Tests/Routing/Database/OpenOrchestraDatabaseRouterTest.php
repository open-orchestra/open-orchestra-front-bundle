<?php

namespace OpenOrchestra\FrontBundle\Tests\Routing\Database;

use Doctrine\Common\Collections\ArrayCollection;
use OpenOrchestra\FrontBundle\Routing\Database\OpenOrchestraDatabaseRouter;
use Phake;

/**
 * Test OpenOrchestraDatabaseRouterTest
 */
class OpenOrchestraDatabaseRouterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OpenOrchestraDatabaseRouter
     */
    protected $router;

    protected $context;
    protected $routeDocumentRepository;
    protected $routeDocumentToValueObjectTransformer;

    /**
     * Set up the test
     */
    public function setUp()
    {
        $this->context = Phake::mock('Symfony\Component\Routing\RequestContext');
        $this->routeDocumentRepository = Phake::mock('OpenOrchestra\ModelInterface\Repository\RouteDocumentRepositoryInterface');
        $this->routeDocumentToValueObjectTransformer = Phake::mock('OpenOrchestra\FrontBundle\Routing\Database\Transformer\RouteDocumentToValueObjectTransformer');
        $requestStack = Phake::mock('Symfony\Component\HttpFoundation\RequestStack');
        $nodeManager = Phake::mock('OpenOrchestra\FrontBundle\Manager\NodeManager');

        $this->router = new OpenOrchestraDatabaseRouter($this->routeDocumentRepository, $this->routeDocumentToValueObjectTransformer, $requestStack, $nodeManager);
        $this->router->setContext($this->context);
    }

    /**
     * Test instance
     */
    public function testInstance()
    {
        $this->assertInstanceOf('Symfony\Component\Routing\RouterInterface', $this->router);
    }

    /**
     * Test get set context
     */
    public function testGetContext()
    {
        $this->assertSame($this->context, $this->router->getContext());
    }

    /**
     * Test get route collection
     */
    public function testGetRouteCollection()
    {
        $name = 'foo';
        $routeDocument = Phake::mock('OpenOrchestra\ModelInterface\Model\RouteDocumentInterface');
        Phake::when($routeDocument)->getName()->thenReturn($name);
        $routeDocuments = new ArrayCollection(array($routeDocument));
        Phake::when($this->routeDocumentRepository)->findAll()->thenReturn($routeDocuments);

        $route = Phake::mock('Symfony\Component\Routing\Route');
        Phake::when($this->routeDocumentToValueObjectTransformer)->transform(Phake::anyParameters())->thenReturn($route);

        $routeCollection = $this->router->getRouteCollection();

        $this->assertInstanceOf('Symfony\Component\Routing\RouteCollection', $routeCollection);
        $this->assertCount(1, $routeCollection);
        $this->assertSame($route, $routeCollection->get($name));
    }

    /**
     * Test get generator
     */
    public function testGetGenerator()
    {
        $generator = $this->router->getGenerator();

        $this->assertInstanceOf('Symfony\Component\Routing\Generator\UrlGeneratorInterface', $generator);
        $this->assertInstanceOf('OpenOrchestra\FrontBundle\Routing\Database\OpenOrchestraDatabaseUrlGenerator', $generator);
    }
}
