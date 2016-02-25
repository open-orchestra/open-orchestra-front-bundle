<?php

namespace OpenOrchestra\FrontBundle\Tests\Routing\Database;

use OpenOrchestra\BaseBundle\Tests\AbstractTest\AbstractBaseTestCase;
use OpenOrchestra\DisplayBundle\Exception\NodeNotFoundException;
use OpenOrchestra\FrontBundle\Routing\Database\OpenOrchestraDatabaseLinkGenerator;
use Phake;

/**
 * Test OpenOrchestraDatabaseLinkGeneratorTest
 */
class OpenOrchestraDatabaseLinkGeneratorTest extends AbstractBaseTestCase
{
    /**
     * @var OpenOrchestraDatabaseLinkGenerator
     */
    protected $generator;

    protected $context;
    protected $nodeManager;
    protected $routeName = 'foo';
    protected $routeFullName = '0_foo';
    protected $routeDocumentRepository;
    protected $routeDocumentToValueObjectTransformer;

    /**
     * Set up the test
     */
    public function setUp()
    {
        $this->context = Phake::mock('Symfony\Component\Routing\RequestContext');
        $this->routeDocumentRepository = Phake::mock('OpenOrchestra\ModelInterface\Repository\RouteDocumentRepositoryInterface');
        $this->nodeManager = Phake::mock('OpenOrchestra\FrontBundle\Manager\NodeManager');

        $request = Phake::mock('Symfony\Component\HttpFoundation\Request');
        Phake::when($request)->get(Phake::anyParameters())->thenReturn(0);
        $requestStack = Phake::mock('Symfony\Component\HttpFoundation\RequestStack');
        Phake::when($requestStack)->getMasterRequest()->thenReturn($request);

        $route = Phake::mock('Symfony\Component\Routing\Route');
        Phake::when($route)->compile()->thenThrow(new GeneratedRouteCompiledException());
        $this->routeDocumentToValueObjectTransformer = Phake::mock('OpenOrchestra\FrontBundle\Routing\Database\Transformer\RouteDocumentToValueObjectTransformer');
        Phake::when($this->routeDocumentToValueObjectTransformer)->transform(Phake::anyParameters())->thenReturn($route);

        $this->generator = new OpenOrchestraDatabaseLinkGenerator(
            $this->routeDocumentRepository,
            $this->routeDocumentToValueObjectTransformer,
            $requestStack,
            $this->nodeManager,
            $this->context
        );
        $this->generator->setContext($this->context);
    }

    /**
     * test instance
     */
    public function testInstance()
    {
        $this->assertInstanceOf('Symfony\Component\Routing\Generator\UrlGeneratorInterface', $this->generator);
        $this->assertInstanceOf('Symfony\Component\Routing\Generator\UrlGenerator', $this->generator);
    }

    /**
     * Test get context
     */
    public function testGetSetContext()
    {
        $this->assertSame($this->context, $this->generator->getContext());
    }

    /**
     * Test normal case
     */
    public function testGenerateNormalCase()
    {
        $routeDocument = Phake::mock('OpenOrchestra\ModelInterface\Model\RouteDocumentInterface');
        Phake::when($this->routeDocumentRepository)->findOneByName($this->routeFullName)->thenReturn($routeDocument);

        $this->setExpectedException('OpenOrchestra\FrontBundle\Tests\Routing\Database\GeneratedRouteCompiledException');
        $this->generator->generate($this->routeName, array('aliasId' => 0));
    }

    /**
     * Test with no route in database
     */
    public function testWithNoRouteInDatabase()
    {
        $this->setExpectedException('Symfony\Component\Routing\Exception\RouteNotFoundException');
        $this->generator->generate($this->routeName);
    }

    /**
     * Test when a redirection is asked but no node present
     */
    public function testWithNoRedirection()
    {
        Phake::when($this->nodeManager)->getNodeRouteName(Phake::anyParameters())->thenThrow(new NodeNotFoundException());

        $this->setExpectedException('Symfony\Component\Routing\Exception\RouteNotFoundException');
        $this->generator->generate($this->routeName);
    }
}
