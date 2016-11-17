<?php

namespace OpenOrchestra\FrontBundle\Twig;

use OpenOrchestra\BaseBundle\Tests\AbstractTest\AbstractBaseTestCase;
use OpenOrchestra\FrontBundle\Exception\NonExistingAreaException;
use OpenOrchestra\ModelInterface\Model\ReadAreaInterface;
use OpenOrchestra\ModelInterface\Model\ReadNodeInterface;
use Phake;

/**
 * Class RenderAreaExtensionTest
 */
class RenderAreaExtensionTest extends AbstractBaseTestCase
{
    /**
     * @var RenderAreaExtension
     */
    protected $extension;
    protected $twigEnvironment;

    /**
     * Set up
     */
    public function setUp()
    {
        $this->twigEnvironment = Phake::mock(\Twig_Environment::class);
        $this->extension = new RenderAreaExtension();
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
        $this->assertSame('render_area', $this->extension->getName());
    }

    /**
     * Test method count
     */
    public function testFunction()
    {
        $this->assertCount(1, $this->extension->getFunctions());
    }

    /**
     * Test render area
     */
    public function testRenderArea()
    {
        $node = Phake::mock(ReadNodeInterface::class);
        $area = Phake::mock(ReadAreaInterface::class);
        $name = 'fakeName';
        $nodeId = 'fakeNodeId';
        $siteId = 'fakeSiteId';
        $language = 'fakeLanguage';
        Phake::when($node)->getArea($name)->thenReturn($area);
        Phake::when($node)->getNodeId()->thenReturn($nodeId);
        Phake::when($node)->getSiteId()->thenReturn($siteId);
        Phake::when($node)->getLanguage()->thenReturn($language);

        $parameters = array(
            'area'       => $area,
            'parameters' => array(),
            'nodeId'     => $nodeId,
            'siteId'     => $siteId,
            '_locale'    => $language
        );

        $this->extension->renderArea($this->twigEnvironment, $name, $node);

        Phake::verify($this->twigEnvironment)->render("OpenOrchestraFrontBundle:Node:area.html.twig", $parameters);
    }

    /**
     * Test render area with exception
     */
    public function testRenderAreaException()
    {
        $node = Phake::mock(ReadNodeInterface::class);
        Phake::when($node)->getArea()->thenReturn(null);
        $name = 'fakeName';

        $this->expectException(NonExistingAreaException::class);
        $this->extension->renderArea($this->twigEnvironment, $name, $node);
    }
}
