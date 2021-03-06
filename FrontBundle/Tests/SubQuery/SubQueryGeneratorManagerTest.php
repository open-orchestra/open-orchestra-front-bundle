<?php

namespace OpenOrchestra\FrontBundle\Tests\SubQuery;

use OpenOrchestra\BaseBundle\Tests\AbstractTest\AbstractBaseTestCase;
use OpenOrchestra\FrontBundle\SubQuery\SubQueryGeneratorManager;
use Phake;

/**
 * Test SubQueryGeneratorManagerTest
 */
class SubQueryGeneratorManagerTest extends AbstractBaseTestCase
{
    /**
     * @var SubQueryGeneratorManager
     */
    protected $manager;

    protected $strategy;
    protected $displayBlockManager;

    /**
     * Set up the test
     */
    public function setUp()
    {
        $this->displayBlockManager = Phake::mock('OpenOrchestra\DisplayBundle\DisplayBlock\DisplayBlockManager');
        $this->strategy = Phake::mock('OpenOrchestra\FrontBundle\SubQuery\SubQueryGeneratorInterface');
        Phake::when($this->strategy)->getName()->thenReturn('foo');

        $this->manager = new SubQueryGeneratorManager($this->displayBlockManager);
        $this->manager->addStrategy($this->strategy);
    }

    /**
     * @param bool  $support
     * @param array $blockParameters
     * @param bool  $blockPrivate
     * @param array $strategyResponse
     * @param array $baseSubQuery
     * @param array $expected
     *
     * @dataProvider provideGenerateData
     */
    public function testGenerate($support, array $blockParameters, $blockPrivate, array $strategyResponse, array $baseSubQuery, array $expected)
    {
        Phake::when($this->strategy)->support(Phake::anyParameters())->thenReturn($support);
        Phake::when($this->strategy)->generate(Phake::anyParameters())->thenReturn($strategyResponse);
        $block = Phake::mock('OpenOrchestra\ModelInterface\Model\BlockInterface');
        Phake::when($this->displayBlockManager)->isPublic($block)->thenReturn(!$blockPrivate);
        Phake::when($this->displayBlockManager)->getBlockParameter($block)->thenReturn($blockParameters);

        $this->assertSame($expected, $this->manager->generate($block, $baseSubQuery));
    }

    /**
     * @return array
     */
    public function provideGenerateData()
    {
        $fooBarArray = array('foo' => 'bar');
        $fooBarArrayWithCache = array('foo' => 'bar', 'cache' => 'private');

        return array(
            array(false, array(), false, array(), array(), array()),
            array(true, array(), false, array(), array(), array()),
            array(true, array('foo'), false, $fooBarArray, array(), $fooBarArray),
            array(true, array('foo'), false, array(), $fooBarArray, $fooBarArray),
            array(false, array('foo'), false, $fooBarArray, array(), array()),
            array(true, array('foo'), true, $fooBarArray, array('foo' => 'foo'), $fooBarArrayWithCache),
            array(true, array('foo', 'bar'), true, $fooBarArray, array('foo' => 'foo'), $fooBarArrayWithCache),
        );
    }
}
