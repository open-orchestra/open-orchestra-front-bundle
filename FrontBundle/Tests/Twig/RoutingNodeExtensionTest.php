<?php

namespace OpenOrchestra\FrontBundle\Twig;

use OpenOrchestra\BaseBundle\Context\CurrentSiteIdInterface;
use OpenOrchestra\BaseBundle\Tests\AbstractTest\AbstractBaseTestCase;
use OpenOrchestra\DisplayBundle\Exception\NodeNotFoundException;
use OpenOrchestra\ModelInterface\Model\ReadNodeInterface;
use OpenOrchestra\ModelInterface\Repository\ReadNodeRepositoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Phake;

/**
 * Class RoutingNodeExtensionTest
 */
class RoutingNodeExtensionTest extends AbstractBaseTestCase
{
    /**
     * @var RoutingNodeExtension
     */
    protected $extension;

    protected $generatorUrl;
    protected $nodeRepository;

    /**
     * Set up
     */
    public function setUp()
    {
        $this->generatorUrl = Phake::mock(UrlGeneratorInterface::class);
        $this->nodeRepository = Phake::mock(ReadNodeRepositoryInterface::class);
        $siteManager = Phake::mock(CurrentSiteIdInterface::class);
        $this->extension = new RoutingNodeExtension($this->generatorUrl, $siteManager, $this->nodeRepository);
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
        $this->assertSame('routing_node', $this->extension->getName());
    }

    /**
     * Test method count
     */
    public function testFunction()
    {
        $this->assertCount(2, $this->extension->getFunctions());
    }

    /**
     * Test get path node
     */
    public function testGetPathNode()
    {
        $id = 'fakeId';
        $nodeId = 'nodeId';
        $node = Phake::mock(ReadNodeInterface::class);
        Phake::when($node)->getId()->thenReturn($id);
        Phake::when($this->nodeRepository)->findOnePublished(Phake::anyParameters())->thenReturn($node);

        $this->extension->getPathNode($nodeId);

        Phake::verify($this->generatorUrl)->generate($id, array(),  UrlGeneratorInterface::ABSOLUTE_PATH);
    }

    /**
     * Test get path node with exception
     */
    public function testGetPathNodeException()
    {
        $nodeId = 'nodeId';
        Phake::when($this->nodeRepository)->findOnePublished(Phake::anyParameters())->thenReturn(null);

        $this->expectException(NodeNotFoundException::class);
        $this->extension->getPathNode($nodeId);
    }

    /**
     * Test get url node
     */
    public function testGetUrlNode()
    {
        $id = 'fakeId';
        $nodeId = 'nodeId';
        $node = Phake::mock(ReadNodeInterface::class);
        Phake::when($node)->getId()->thenReturn($id);
        Phake::when($this->nodeRepository)->findOnePublished(Phake::anyParameters())->thenReturn($node);

        $this->extension->getUrlNode($nodeId);

        Phake::verify($this->generatorUrl)->generate($id, array(), UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * Test get url node with exception
     */
    public function testGetUrlNodeException()
    {
        $nodeId = 'nodeId';
        Phake::when($this->nodeRepository)->findOnePublished(Phake::anyParameters())->thenReturn(null);

        $this->expectException(NodeNotFoundException::class);
        $this->extension->getUrlNode($nodeId);
    }
}
