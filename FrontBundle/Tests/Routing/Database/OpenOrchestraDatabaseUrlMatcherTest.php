<?php

namespace OpenOrchestra\FrontBundle\Tests\Routing\Database;

use Doctrine\Common\Collections\ArrayCollection;
use OpenOrchestra\BaseBundle\Tests\AbstractTest\AbstractBaseTestCase;
use OpenOrchestra\FrontBundle\Routing\Database\OpenOrchestraDatabaseUrlMatcher;
use Phake;
use Symfony\Component\Routing\RouteCollection;

/**
 * Test OpenOrchestraDatabaseUrlMatcherTest
 */
class OpenOrchestraDatabaseUrlMatcherTest extends AbstractBaseTestCase
{
    /**
     * @var OpenOrchestraDatabaseUrlMatcher
     */
    protected $urlMatcher;

    protected $routeTransformer;
    protected $routeRepository;

    /**
     * Set up the test
     */
    public function setUp()
    {
        $context = Phake::mock('Symfony\Component\Routing\RequestContext');
        $this->routeTransformer = Phake::mock('OpenOrchestra\FrontBundle\Routing\Database\Transformer\RouteDocumentCollectionToRouteCollectionTransformer');
        $this->routeRepository = Phake::mock('OpenOrchestra\ModelInterface\Repository\RouteDocumentRepositoryInterface');

        $this->urlMatcher = new OpenOrchestraDatabaseUrlMatcher(
            $context,
            $this->routeTransformer,
            $this->routeRepository
        );
    }

    /**
     * Test instance
     */
    public function testInstance()
    {
        $this->assertInstanceOf('Symfony\Component\Routing\Matcher\UrlMatcherInterface', $this->urlMatcher);
        $this->assertInstanceOf('Symfony\Component\Routing\Matcher\UrlMatcher', $this->urlMatcher);
    }

    /**
     * Test match
     */
    public function testMatch()
    {
        $route = Phake::mock('Symfony\Component\Routing\Route');
        Phake::when($route)->compile()->thenThrow(new MatchedRouteCompiledException());

        $routeCollection = new RouteCollection();
        $routeCollection->add('foo', $route);

        Phake::when($this->routeTransformer)->transform(Phake::anyParameters())->thenReturn($routeCollection);
        Phake::when($this->routeRepository)->findByPathInfo(Phake::anyParameters())->thenReturn(new ArrayCollection());

        $this->setExpectedException('OpenOrchestra\FrontBundle\Tests\Routing\Database\MatchedRouteCompiledException');
        $this->urlMatcher->match('foo');
    }
}

/**
 * Class MatchedRouteCompiledException
 *
 * Thrown when the route is compiled
 */
class MatchedRouteCompiledException extends \Exception
{
}
