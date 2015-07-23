<?php

namespace OpenOrchestra\FrontBundle\Tests\Twig;

use OpenOrchestra\FrontBundle\Twig\SubQueryGeneratorExtension;
use Phake;

/**
 * Test SubQueryGeneratorExtensionTest
 */
class SubQueryGeneratorExtensionTest extends \PHPUnit_Framework_TestCase
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
        Phake::when($this->manager)->generate(Phake::anyParameters())->thenReturn($subQuery);

        $this->assertSame($subQuery, $this->extension->generateSubQuery(array('foo'), array('bar')));
        Phake::verify($this->manager)->generate(array('foo'), array('bar'));
    }
}
