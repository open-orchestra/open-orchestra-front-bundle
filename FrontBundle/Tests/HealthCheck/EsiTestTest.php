<?php

namespace OpenOrchestra\FrontBundle\Tests\HealthCheck;

use OpenOrchestra\BaseBundle\HealthCheck\HealthCheckTestResult;
use OpenOrchestra\BaseBundle\HealthCheck\HealthCheckTestResultInterface;
use OpenOrchestra\BaseBundle\Tests\AbstractTest\AbstractBaseTestCase;
use OpenOrchestra\FrontBundle\HealthCheck\EsiTest;
use Phake;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Class EsiTestTest
 */
class EsiTestTest extends AbstractBaseTestCase
{
    /** @var EsiTest */
    protected $test;
    protected $request;

    public function setUp()
    {
        $requestStack = Phake::mock(RequestStack::class);
        $this->request = Phake::mock(Request::class);
        Phake::when($requestStack)->getMasterRequest()->thenReturn($this->request);

        $this->test = new EsiTest($requestStack);
        $this->test->setHealthCheckResultClass(HealthCheckTestResult::class);
    }

    /**
     * @param string $header
     * @param bool   $error
     * @param int    $level
     *
     * @dataProvider provideRequestHeader
     */
    public function testRun($header, $error, $level)
    {
        $this->request->headers = Phake::mock(ResponseHeaderBag::class);
        Phake::when($this->request->headers)->get('Surrogate-Capability')->thenReturn($header);

        $result = $this->test->run();
        $this->assertInstanceOf(HealthCheckTestResult::class, $result);
        $this->assertEquals($error, $result->isError());
        $this->assertEquals($level, $result->getLevel());
    }

    /**
     * @return array
     */
    public function provideRequestHeader()
    {
        return array(
            array('fake', true, HealthCheckTestResultInterface::WARNING),
            array('ESI/1.0', false, HealthCheckTestResultInterface::OK),
        );
    }
}
