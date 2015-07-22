<?php

namespace OpenOrchestra\FrontBundle\Tests\SubQuery\Strategies;

use OpenOrchestra\FrontBundle\SubQuery\Strategies\DeviceSubQueryStrategy;
use Phake;

/**
 * Test DeviceSubQueryStrategyTest
 */
class DeviceSubQueryStrategyTest extends AbstractSubQueryStrategyTest
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

        $this->strategy = new DeviceSubQueryStrategy($requestStack);
    }

    /**
     * @return array
     */
    public function provideBlockParameterAndSupport()
    {
        return array(
            array('post_data', false),
            array('request', false),
            array('request.foo', false),
            array('foo', false),
            array('device', true),
            array('device.foo', false),
        );
    }

    /**
     * Test name
     */
    public function testGetName()
    {
        $this->assertSame('device', $this->strategy->getName());
    }

    /**
     * @param string $deviceName
     *
     * @dataProvider provideDeviceNameAndExpected
     */
    public function testGenerate($deviceName, $expected)
    {
        $attributes = Phake::mock('Symfony\Component\HttpFoundation\ParameterBag');
        Phake::when($attributes)->get(Phake::anyParameters())->thenReturn($deviceName);
        $this->request->headers = $attributes;

        $this->assertSame($expected, $this->strategy->generate('post_data'));
    }

    public function provideDeviceNameAndExpected()
    {
        return array(
            array('mobile', array('x-ua-device' => 'mobile')),
            array('phone', array('x-ua-device' => 'phone')),
            array('tablet', array('x-ua-device' => 'tablet')),
            array(null, array()),
        );
    }
}
