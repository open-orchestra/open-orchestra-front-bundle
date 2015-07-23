<?php

namespace OpenOrchestra\FrontBundle\Tests\SubQuery\Strategies;

use OpenOrchestra\FrontBundle\SubQuery\Strategies\RequestSubQueryStrategy;
use Phake;

/**
 * Test RequestSubQueryStrategyTest
 */
class RequestSubQueryStrategyTest extends AbstractSubQueryStrategyTest
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

        $this->strategy = new RequestSubQueryStrategy($requestStack);
    }

    /**
     * Test name
     */
    public function testGetName()
    {
        $this->assertSame('request', $this->strategy->getName());
    }

    /**
     * @param string $blockParameter
     * @param bool   $support
     *
     * @dataProvider provideBlockParameterAndSupport
     */
    public function testSupport($blockParameter, $support)
    {
        $this->assertSame($support, $this->strategy->support($blockParameter));
    }

    /**
     * @return array
     */
    public function provideBlockParameterAndSupport()
    {
        return array(
            array('request', false),
            array('foo', false),
            array('request.contentId', true),
            array('x-ua-device', false),
        );
    }

    /**
     * @param string $blockParameter
     * @param string $requestKey
     * @param string $requestResponse
     *
     * @dataProvider provideParameterAndResponse
     */
    public function testGenerate($blockParameter, $requestKey, $requestResponse)
    {
        Phake::when($this->request)->get(Phake::anyParameters())->thenReturn($requestResponse);

        $this->assertSame(array($requestKey => $requestResponse), $this->strategy->generate($blockParameter));
        Phake::verify($this->request)->get($requestKey);
    }

    /**
     * @return array
     */
    public function provideParameterAndResponse()
    {
        return array(
            array('request.contentId', 'contentId', 'foo'),
            array('request.foo', 'foo', 'bar'),
        );
    }
}
