<?php

namespace OpenOrchestra\FrontBundle\Tests\Routing\Database\Transformer;

use OpenOrchestra\BaseBundle\Tests\AbstractTest\AbstractBaseTestCase;
use OpenOrchestra\FrontBundle\Routing\Database\Transformer\RouteDocumentToValueObjectTransformer;
use Phake;
use Symfony\Component\Routing\RouteCollection;
/**
 * Test RouteDocumentToValueObjectTransformerTest
 */
class RouteDocumentToValueObjectTransformerTest extends AbstractBaseTestCase
{
    /**
     * @var RouteDocumentToValueObjectTransformer
     */
    protected $transformer;

    /**
     * Set up the test
     */
    public function setUp()
    {
        $route = Phake::mock('Symfony\Component\Routing\Route');
        Phake::when($route)->getDefault('_controller')->thenReturn('OpenOrchestra\FrontBundle\Controller\NodeController::showAction');

        $routeCollection = Phake::mock('Symfony\Component\Routing\RouteCollection');
        Phake::when($routeCollection)->get('open_orchestra_front_node')->thenReturn($route);

        $router = Phake::mock('Symfony\Component\Routing\RouterInterface');
        Phake::when($router)->getRouteCollection()->thenReturn($routeCollection);

        $this->transformer = new RouteDocumentToValueObjectTransformer($router);
    }

    /**
     * @param string $pattern
     *
     * @dataProvider provideRouteDocumentParameters
     */
    public function testTransform($pattern)
    {
        $routeDocument = Phake::mock('OpenOrchestra\ModelInterface\Model\RouteDocumentInterface');
        Phake::when($routeDocument)->getPattern()->thenReturn($pattern);
        Phake::when($routeDocument)->getDefaults()->thenReturn(array());
        Phake::when($routeDocument)->getRequirements()->thenReturn(array());
        Phake::when($routeDocument)->getOptions()->thenReturn(array());

        $route = $this->transformer->transform($routeDocument);

        $this->assertInstanceOf('Symfony\Component\Routing\Route', $route);
        $this->assertSame($pattern, $route->getPath());
        Phake::verify($routeDocument)->getPattern();
        Phake::verify($routeDocument)->getDefaults();
        Phake::verify($routeDocument)->getRequirements();
        Phake::verify($routeDocument)->getOptions();
        Phake::verify($routeDocument)->getHost();
        Phake::verify($routeDocument)->getSchemes();
        Phake::verify($routeDocument)->getMethods();
        Phake::verify($routeDocument)->getCondition();
    }

    /**
     * @return array
     */
    public function provideRouteDocumentParameters()
    {
        return array(
            array('/foo'),
        );
    }
}
