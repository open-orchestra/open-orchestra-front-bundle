<?php

namespace OpenOrchestra\FrontBundle\Tests\Routing\Database;

use OpenOrchestra\BaseBundle\Tests\AbstractTest\AbstractBaseTestCase;
use OpenOrchestra\DisplayBundle\Exception\NodeNotFoundException;
use OpenOrchestra\FrontBundle\Routing\Database\OpenOrchestraDatabaseUrlGenerator;
use Phake;

/**
 * Test OpenOrchestraDatabaseUrlGeneratorTest
 */
class OpenOrchestraDatabaseUrlGeneratorTest extends AbstractBaseTestCase
{
    /**
     * @var OpenOrchestraDatabaseUrlGenerator
     */
    protected $generator;

    protected $siteRepository;
    protected $currentSiteManager;
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
        $this->siteRepository = Phake::mock('OpenOrchestra\ModelInterface\Repository\ReadSiteRepositoryInterface');
        $this->currentSiteManager = Phake::mock('OpenOrchestra\BaseBundle\Context\CurrentSiteIdInterface');
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

        $this->generator = new OpenOrchestraDatabaseUrlGenerator(
            $this->routeDocumentRepository,
            $this->siteRepository,
            $this->routeDocumentToValueObjectTransformer,
            $this->currentSiteManager,
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

        $this->setExpectedException('Symfony\Component\Routing\Exception\RouteNotFoundException');
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

        $this->setExpectedException('OpenOrchestra\FrontBundle\Tests\Routing\Database\GeneratedRouteCompiledException');
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
     * Test when a redirection is made and no node is found
     */
    public function testWithRedirectionAndNoExistingNode()
    {
        Phake::when($this->nodeManager)->getNodeRouteName(Phake::anyParameters())->thenThrow(new NodeNotFoundException());

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
 * Class GeneratedRouteCompiledException
 *
 * This exception will be used to avoid the route generation (tested in symfony)
 */
class GeneratedRouteCompiledException extends \Exception
{
}
