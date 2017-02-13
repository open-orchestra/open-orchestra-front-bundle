<?php

namespace OpenOrchestra\FrontBundle\Tests\Twig;

use OpenOrchestra\BaseBundle\Tests\AbstractTest\AbstractBaseTestCase;
use OpenOrchestra\FrontBundle\Twig\SubQueryGeneratorExtension;
use Phake;

/**
 * Test SubQueryGeneratorExtensionTest
 */
class SubQueryGeneratorExtensionTest extends AbstractBaseTestCase
{
    /**
     * @var SubQueryGeneratorExtension
     */
    protected $extension;

    protected $manager;

    /**
     * Set up
     */
    public function setUp()
    {
        $this->manager = Phake::mock('OpenOrchestra\FrontBundle\SubQuery\SubQueryGeneratorManager');

        $this->extension = new SubQueryGeneratorExtension($this->manager);
    }

    /**
     * Test instance
     */
    public function testInstance()
    {
        $this->assertInstanceOf('Twig_Extension', $this->extension);
    }

    /**
     * Test name
     */
    public function testGetName()
    {
        $this->assertSame('sub_query', $this->extension->getName());
    }

    /**
     * Test method count
     */
    public function testFunction()
    {
        $this->assertCount(1, $this->extension->getFunctions());
    }

    /**
     * Test generateSubQuery
     */
    public function testGenerateSubQuery()
    {
        $subQuery = array('foo' => 'bar');
        $block = Phake::mock('OpenOrchestra\ModelInterface\Model\BlockInterface');
        Phake::when($this->manager)->generate(Phake::anyParameters())->thenReturn($subQuery);

        $this->assertSame($subQuery, $this->extension->generateSubQuery($block, array('bar')));
        Phake::verify($this->manager)->generate($block, array('bar'));
    }
}
