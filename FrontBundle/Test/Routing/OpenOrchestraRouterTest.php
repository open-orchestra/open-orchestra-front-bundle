<?php

namespace OpenOrchestra\FrontBundle\Test\Routing;

use Phake;
use OpenOrchestra\FrontBundle\Routing\OpenOrchestraRouter;
use Symfony\Component\Routing\RouteCollection;

/**
 * Tests of OpenOrchestraUrlRouter
 */
class OpenOrchestraRouterTest extends \PHPUnit_Framework_TestCase
{
    protected $router;

    /**
     * Set up the test
     */
    public function setUp()
    {
        $requestStack = Phake::mock('Symfony\Component\HttpFoundation\RequestStack');

        $mockRoutingLoader = Phake::mock('Symfony\Bundle\FrameworkBundle\Routing\DelegatingLoader');
        Phake::when($mockRoutingLoader)->load(Phake::anyParameters())->thenReturn(new RouteCollection());

        $container = Phake::mock('Symfony\Component\DependencyInjection\ContainerInterface');
        Phake::when($container)->get('routing.loader')->thenReturn($mockRoutingLoader);
        Phake::when($container)->get('request_stack')->thenReturn($requestStack);

        $this->router = new OpenOrchestraRouter(
            $container,
            null,
            array(
                'generator_class' => 'OpenOrchestra\FrontBundle\Routing\OpenOrchestraUrlGenerator',
                'generator_base_class' => 'OpenOrchestra\FrontBundle\Routing\OpenOrchestraUrlGenerator',
            )
        );
    }

    /**
     * test get generator
     */
    public function testGetGenerator()
    {
        $this->assertInstanceOf(
            'OpenOrchestra\\FrontBundle\\Routing\\OpenOrchestraUrlGenerator',
            $this->router->getGenerator()
        );
    }
}
