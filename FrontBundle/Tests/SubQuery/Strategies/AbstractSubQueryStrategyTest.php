<?php

namespace OpenOrchestra\FrontBundle\Tests\SubQuery\Strategies;

use OpenOrchestra\BaseBundle\Tests\AbstractTest\AbstractBaseTestCase;
use OpenOrchestra\FrontBundle\SubQuery\SubQueryGeneratorInterface;

/**
 * Class AbstractSubQueryStrategyTest
 */
abstract class AbstractSubQueryStrategyTest extends AbstractBaseTestCase
{
    /**
     * @var SubQueryGeneratorInterface
     */
    protected $strategy;

    /**
     * Test instance
     */
    public function testInstance()
    {
        $this->assertInstanceOf('OpenOrchestra\FrontBundle\SubQuery\SubQueryGeneratorInterface', $this->strategy);
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
    abstract public function provideBlockParameterAndSupport();
}
