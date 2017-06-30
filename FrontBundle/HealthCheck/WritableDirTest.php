<?php

namespace OpenOrchestra\FrontBundle\HealthCheck;

use OpenOrchestra\BaseBundle\HealthCheck\AbstractHealthCheckTest;
use OpenOrchestra\BaseBundle\HealthCheck\HealthCheckTestResultInterface;

/**
 * Class WritableDirTest
 */
class WritableDirTest extends AbstractHealthCheckTest
{
    protected $cacheDir;
    protected $logsDir;

    /**
     * @param string $cacheDir
     * @param string $logsDir
     */
    public function __construct($cacheDir, $logsDir)
    {
        $this->cacheDir = $cacheDir;
        $this->logsDir = $logsDir;
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $label = "ACL cache and log directories";

        if (
            false === $this->testWritableDir($this->cacheDir) ||
            false === $this->testWritableDir($this->logsDir)
        ) {
            return $this->createTestResult(true, $label, HealthCheckTestResultInterface::ERROR);
        }

        return $this->createValidTestResult($label);
    }

    /**
     * @param string $path
     *
     * @return bool
     */
    protected function testWritableDir($path)
    {
        return (is_dir($path) && is_writable($path));
    }
}
