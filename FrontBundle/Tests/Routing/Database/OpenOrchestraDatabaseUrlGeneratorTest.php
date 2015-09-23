<?php

namespace OpenOrchestra\FrontBundle\Tests\Routing\Database;

use OpenOrchestra\DisplayBundle\Exception\NodeNotFoundException;
use OpenOrchestra\FrontBundle\Routing\Database\OpenOrchestraDatabaseUrlGenerator;
use Phake;

/**
 * Test OpenOrchestraDatabaseUrlGeneratorTest
 */
class OpenOrchestraDatabaseUrlGeneratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OpenOrchestraDatabaseUrlGenerator
     */
    protected $generator;

    protected $context;
    protected $nodeManager;
    protected $requestStack;
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
        $this->requestStack = Phake::mock('Symfony\Component\HttpFoundation\RequestStack');

        $route = Phake::mock('Symfony\Component\Routing\Route');
        Phake::when($route)->compile()->thenThrow(new RouteCompiledException());
        $this->routeDocumentToValueObjectTransformer = Phake::mock('OpenOrchestra\FrontBundle\Routing\Database\Transformer\RouteDocumentToValueObjectTransformer');
        Phake::when($this->routeDocumentToValueObjectTransformer)->transform(Phake::anyParameters())->thenReturn($route);

        $this->generator = new OpenOrchestraDatabaseUrlGenerator(
            $this->routeDocumentRepository,
            $this->routeDocumentToValueObjectTransformer,
            $this->requestStack,
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

        $this->setExpectedException('OpenOrchestra\FrontBundle\Tests\Routing\Database\RouteCompiledException');
        $this->generator->generate($this->routeName);
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
     * Test when a redirection is made
     */
    public function testWithRedirection()
    {
        Phake::when($this->nodeManager)->getNodeRouteName(Phake::anyParameters())->thenReturn($this->routeName);

        $routeDocument = Phake::mock('OpenOrchestra\ModelInterface\Model\RouteDocumentInterface');
        Phake::when($this->routeDocumentRepository)->findOneByName($this->routeName)->thenReturn($routeDocument);

        $this->setExpectedException('OpenOrchestra\FrontBundle\Tests\Routing\Database\RouteCompiledException');
        $this->generator->generate($this->routeName, array(OpenOrchestraDatabaseUrlGenerator::REDIRECT_TO_LANGUAGE => true));
    }

    /**
     * Test when a redirection is made
     */
    public function testWithRedirectionAndNoExistingRoute()
    {
        Phake::when($this->nodeManager)->getNodeRouteName(Phake::anyParameters())->thenReturn($this->routeName);

        $this->setExpectedException('Symfony\Component\Routing\Exception\RouteNotFoundException');
        $this->generator->generate($this->routeName, array(OpenOrchestraDatabaseUrlGenerator::REDIRECT_TO_LANGUAGE => true));
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

/**
 * Class RouteCompiledException
 *
 * This exception will be used to avoid the route generation (tested in symfony)
 */
class RouteCompiledException extends \Exception
{
}
