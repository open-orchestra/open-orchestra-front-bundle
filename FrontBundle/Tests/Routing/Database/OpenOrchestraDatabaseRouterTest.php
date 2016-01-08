<?php

namespace OpenOrchestra\FrontBundle\Tests\Routing\Database;

use Doctrine\Common\Collections\ArrayCollection;
use OpenOrchestra\BaseBundle\Tests\AbstractTest\AbstractBaseTestCase;
use OpenOrchestra\FrontBundle\Routing\Database\OpenOrchestraDatabaseRouter;
use Phake;

/**
 * Test OpenOrchestraDatabaseRouterTest
 */
class OpenOrchestraDatabaseRouterTest extends AbstractBaseTestCase
{
    /**
     * @var OpenOrchestraDatabaseRouter
     */
    protected $router;

    protected $context;
    protected $routeDocumentRepository;
    protected $routeDocumentToValueObjectTransformer;
    protected $routeDocumentCollectionToRouteCollectionTransformer;

    /**
     * Set up the test
     */
    public function setUp()
    {
        $this->context = Phake::mock('Symfony\Component\Routing\RequestContext');
        $this->routeDocumentRepository = Phake::mock('OpenOrchestra\ModelInterface\Repository\RouteDocumentRepositoryInterface');
        $this->routeDocumentToValueObjectTransformer = Phake::mock('OpenOrchestra\FrontBundle\Routing\Database\Transformer\RouteDocumentToValueObjectTransformer');
        $this->routeDocumentCollectionToRouteCollectionTransformer = Phake::mock('OpenOrchestra\FrontBundle\Routing\Database\Transformer\RouteDocumentCollectionToRouteCollectionTransformer');
        $requestStack = Phake::mock('Symfony\Component\HttpFoundation\RequestStack');
        $nodeManager = Phake::mock('OpenOrchestra\FrontBundle\Manager\NodeManager');

        $this->router = new OpenOrchestraDatabaseRouter(
            $this->routeDocumentRepository,
            $this->routeDocumentToValueObjectTransformer,
            $this->routeDocumentCollectionToRouteCollectionTransformer,
            $requestStack,
            $nodeManager
        );
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
        $routeDocument = Phake::mock('OpenOrchestra\ModelInterface\Model\RouteDocumentInterface');
        $routeDocuments = new ArrayCollection(array($routeDocument));
        Phake::when($this->routeDocumentRepository)->findAll()->thenReturn($routeDocuments);

        $routeCollectionGenerated = Phake::mock('Symfony\Component\Routing\RouteCollection');
        Phake::when($this->routeDocumentCollectionToRouteCollectionTransformer)->transform(Phake::anyParameters())->thenReturn($routeCollectionGenerated);

        $routeCollection = $this->router->getRouteCollection();

        $this->assertSame($routeCollectionGenerated, $routeCollection);
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

    /**
     * Test get matcher
     */
    public function testGetMatcher()
    {
        $matcher = $this->router->getMatcher();

        $this->assertInstanceOf('Symfony\Component\Routing\Matcher\UrlMatcherInterface', $matcher);
        $this->assertInstanceOf('OpenOrchestra\FrontBundle\Routing\Database\OpenOrchestraDatabaseUrlMatcher', $matcher);
    }
}
