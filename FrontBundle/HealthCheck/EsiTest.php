<?php

namespace OpenOrchestra\FrontBundle\HealthCheck;

use OpenOrchestra\BaseBundle\HealthCheck\AbstractHealthCheckTest;
use OpenOrchestra\BaseBundle\HealthCheck\HealthCheckTestResultInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class EsiTest
 */
class EsiTest extends AbstractHealthCheckTest
{
    protected $requestStack;

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $label = "ESI support";
        $request = $this->requestStack->getMasterRequest();

        if (null === $request ||
            null === ($value = $request->headers->get('Surrogate-Capability')) ||
            false === strpos($value, 'ESI/1.0')
        ) {
            return $this->createTestResult(true, $label, HealthCheckTestResultInterface::WARNING);
        }

        return $this->createValidTestResult($label);
    }
}
