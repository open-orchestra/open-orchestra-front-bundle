<?php

namespace OpenOrchestra\FrontBundle\Tests\HealthCheck;

use OpenOrchestra\BaseBundle\HealthCheck\HealthCheckTestResult;
use OpenOrchestra\BaseBundle\HealthCheck\HealthCheckTestResultInterface;
use OpenOrchestra\BaseBundle\Tests\AbstractTest\AbstractBaseTestCase;
use OpenOrchestra\FrontBundle\HealthCheck\WritableDirTest;

/**
 * Class WritableDirTestTest
 */
class WritableDirTestTest extends AbstractBaseTestCase
{
    /**
     * Test run
     */
    public function testRun()
    {
        $test = new WritableDirTest('fakeCacheDir', 'fakeLogDir');
        $test->setHealthCheckResultClass(HealthCheckTestResult::class);
        $result = $test->run();
        $this->assertInstanceOf(HealthCheckTestResult::class, $result);
        $this->assertEquals(true, $result->isError());
        $this->assertEquals(HealthCheckTestResultInterface::ERROR, $result->getLevel());
    }
}
