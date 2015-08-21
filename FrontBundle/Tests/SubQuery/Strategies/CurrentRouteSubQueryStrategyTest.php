<?php

namespace OpenOrchestra\FrontBundle\Tests\SubQuery\Strategies;

use OpenOrchestra\FrontBundle\SubQuery\Strategies\CurrentRouteSubQueryStrategy;
use Phake;

/**
 * Test CurrentRouteSubQueryStrategyTest
 */
class CurrentRouteSubQueryStrategyTest extends AbstractSubQueryStrategyTest
{
    protected $request;

    /**
     * Set up the test
     */
    public function setUp()
    {
        $this->request = Phake::mock('Symfony\Component\HttpFoundation\Request');
        $requestStack = Phake::mock('Symfony\Component\HttpFoundation\RequestStack');
        Phake::when($requestStack)->getMasterRequest()->thenReturn($this->request);

        $this->strategy = new CurrentRouteSubQueryStrategy($requestStack);
    }

    /**
     * @return array
     */
    public function provideBlockParameterAndSupport()
    {
        return array(
            array('request.contentId', false),
            array('foo', false),
            array('request', false),
            array('post_data', false),
            array('post_data_empty', false),
            array('current_route', true),
            array('current_route_test', false),
        );
    }

    /**
     * Test name
     */
    public function testGetName()
    {
        $this->assertSame('current_route', $this->strategy->getName());
    }

    /**
     * Test generate
     */
    public function testGenerate()
    {
        $route = 'route';
        Phake::when($this->request)->get('_route')->thenReturn($route);

        $this->assertSame(array('currentRouteName' => $route), $this->strategy->generate('current_route'));
    }
}
