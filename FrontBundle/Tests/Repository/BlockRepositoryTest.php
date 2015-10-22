<?php

namespace OpenOrchestra\FrontBundle\Tests\Repository;

use OpenOrchestra\FrontBundle\Repository\BlockRepository;
use Phake;

/**
 * Test BlockRepositoryTest
 */
class BlockRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var BlockRepository
     */
    protected $repository;

    protected $encryptionManager;
    protected $nodeRepository;
    protected $block;
    protected $node;

    /**
     * Set up the test
     */
    public function setUp()
    {
        $this->encryptionManager = Phake::mock('OpenOrchestra\BaseBundle\Manager\EncryptionManager');

        $this->block = Phake::mock('OpenOrchestra\ModelInterface\Model\ReadBlockInterface');
        $this->node = Phake::mock('OpenOrchestra\ModelInterface\Model\ReadNodeInterface');
        Phake::when($this->node)->getBlock(Phake::anyParameters())->thenReturn($this->block);

        $this->nodeRepository = Phake::mock('OpenOrchestra\ModelInterface\Repository\ReadNodeRepositoryInterface');

        $this->repository = new BlockRepository($this->nodeRepository, $this->encryptionManager);
    }

    /**
     * @param string $siteId
     * @param string $nodeId
     * @param int    $blockId
     * @param string $language
     *
     * @dataProvider provideNodeData
     */
    public function testFindBlockWithNoPreviewAndExistingBlock($siteId, $nodeId, $blockId, $language)
    {
        Phake::when($this->nodeRepository)->findPublishedInLastVersion(Phake::anyParameters())->thenReturn($this->node);

        $block = $this->repository->findBlock($blockId, $nodeId, $language, $siteId);

        $this->assertSame($this->block, $block);
        Phake::verify($this->node)->getBlock($blockId);
        Phake::verify($this->nodeRepository)->findPublishedInLastVersion($nodeId, $language, $siteId);
    }

    /**
     * @return array
     */
    public function provideNodeData()
    {
        return array(
            array('site', 'fooNode', 2, 'fr'),
            array('siteBar', 'barNode', 3, 'en'),
        );
    }

    /**
     * @param string $siteId
     * @param string $nodeId
     * @param int    $blockId
     * @param string $language
     *
     * @dataProvider provideNodeData
     */
    public function testFindBlockWithNoPreviewAndNoBlock($siteId, $nodeId, $blockId, $language)
    {
        Phake::when($this->nodeRepository)->findPublishedInLastVersion(Phake::anyParameters())->thenReturn($this->node);
        Phake::when($this->node)->getBlock(Phake::anyParameters())->thenReturn(null);

        $this->setExpectedException('OpenOrchestra\FrontBundle\Exception\NonExistingBlockException');

        $this->repository->findBlock($blockId, $nodeId, $language, $siteId);
    }

    /**
     * @param string $siteId
     * @param string $nodeId
     * @param string $previewNodeId
     * @param int    $blockId
     * @param string $language
     * @param string $previewToken
     *
     * @dataProvider provideNodeDataWithPreview
     */
    public function testFindBlockWithPreviewAndExistingBlock($siteId, $nodeId, $previewNodeId, $blockId, $language, $previewToken, $previewNodeCallCount)
    {
        Phake::when($this->encryptionManager)->decrypt(Phake::anyParameters())->thenReturn($previewToken);
        $previewNode = Phake::mock('OpenOrchestra\ModelInterface\Model\ReadNodeInterface');
        Phake::when($previewNode)->getNodeId()->thenReturn($previewNodeId);
        Phake::when($previewNode)->getBlock(Phake::anyParameters())->thenReturn($this->block);

        Phake::when($this->nodeRepository)->findPublishedInLastVersion(Phake::anyParameters())->thenReturn($this->node);
        Phake::when($this->nodeRepository)->find(Phake::anyParameters())->thenReturn($previewNode);

        $block = $this->repository->findBlock($blockId, $nodeId, $language, $siteId, $previewToken);

        $this->assertSame($this->block, $block);
        Phake::verify($previewNode, Phake::times($previewNodeCallCount))->getBlock($blockId);
        Phake::verify($this->node, Phake::times(1 - $previewNodeCallCount))->getBlock($blockId);
    }

    /**
     * @return array
     */
    public function provideNodeDataWithPreview()
    {
        return array(
            array('site', 'fooNode', 'previewNodeId', 2, 'fr', 'preview', 0),
            array('siteBar', 'barNode', 'previewNodeId', 3, 'en', 'preview_token', 0),
            array('siteBar', 'barNode', 'barNode', 3, 'en', 'preview_token', 1),
        );
    }

    /**
     * @param string     $nodeId
     * @param mixed|null $previewNode
     *
     * @dataProvider provideNonExistingNode
     */
    public function testFindBlockWithNoNode($nodeId, $previewNode = null)
    {
        Phake::when($this->nodeRepository)->find(Phake::anyParameters())->thenReturn($previewNode);

        $this->setExpectedException('OpenOrchestra\FrontBundle\Exception\NonExistingNodeException');
        $this->repository->findBlock('blockId', $nodeId, 'fr', 'siteId');
    }

    /**
     * @return array
     */
    public function provideNonExistingNode()
    {
        $nodeId = 'previewNodeId';
        $previewNode = Phake::mock('OpenOrchestra\ModelInterface\Model\ReadNodeInterface');
        Phake::when($previewNode)->getNodeId()->thenReturn($nodeId);

        return array(
            array('nodeId'),
            array($nodeId, $previewNode),
        );
    }
}
