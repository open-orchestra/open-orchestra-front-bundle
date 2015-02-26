<?php

namespace OpenOrchestra\FrontBundle\Test\Routing;

use Phake;
use OpenOrchestra\FrontBundle\Routing\OpenOrchestraUrlGenerator;

/**
 * Tests of OpenOrchestraUrlGenerator
 */
class OpenOrchestraUrlGeneratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OpenOrchestraUrlGenerator
     */
    protected $generator;

    protected $routes;
    protected $context;
    protected $request;
    protected $requestStack;
    protected $aliasId = '1';

    /**
     * Set up the test
     */
    public function setUp()
    {
        $this->request = Phake::mock('Symfony\Component\HttpFoundation\Request');
        Phake::when($this->request)->get(Phake::anyParameters())->thenReturn($this->aliasId);
        $this->requestStack = Phake::mock('Symfony\Component\HttpFoundation\RequestStack');
        Phake::when($this->requestStack)->getMasterRequest(Phake::anyParameters())->thenReturn($this->request);

        $this->routes = Phake::mock('Symfony\Component\Routing\RouteCollection');
        Phake::when($this->routes)->get(Phake::anyParameters())->thenReturn(null);

        $this->context = Phake::mock('Symfony\Component\Routing\RequestContext');

        $this->generator = new OpenOrchestraUrlGenerator(
            $this->routes,
            $this->context,
            $this->requestStack
        );
    }

    /**
     * test Exception thrown
     */
    public function testExceptionThrown()
    {
        $this->setExpectedException('Symfony\Component\Routing\Exception\RouteNotFoundException');

        $this->generator->generate('route');

        Phake::verify($this->request)->get('aliasId', '0');
        Phake::verify($this->routes, Phake::times(2))->get(Phake::anyParameters());
    }
}
