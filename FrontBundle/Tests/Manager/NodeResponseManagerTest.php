<?php

namespace OpenOrchestra\FrontBundle\Tests\Manager;

use OpenOrchestra\BaseBundle\Tests\AbstractTest\AbstractBaseTestCase;
use OpenOrchestra\FrontBundle\Manager\NodeResponseManager;
use OpenOrchestra\ModelInterface\Model\NodeInterface;
use Phake;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Test NodeResponseManagerTest
 */
class NodeResponseManagerTest extends AbstractBaseTestCase
{
    protected $nodeRepository;
    protected $transverseNode;

    protected $maxAge = array(
        'block1' => 75,
        'block2' => 125,
        'block3' => 25,
        'blockTransverse' => 50,
        'blockPrivate' => 20,
        'publicNode' => 12,
        'privateNode' => 500
    );

    protected $isPublic = array(
        'block1' => true,
        'block2' => true,
        'block3' => true,
        'blockTransverse' => true,
        'blockPrivate' => false
    );

    /**
     * Set up the test
     */
    public function setUp()
    {
        $this->transverseNode = Phake::mock('OpenOrchestra\ModelInterface\Model\NodeInterface');

        $this->nodeRepository = Phake::mock('OpenOrchestra\ModelInterface\Repository\NodeRepositoryInterface');
        Phake::when($this->nodeRepository)->findInLastVersion(Phake::anyParameters())
            ->thenReturn($this->transverseNode);
    }

    /**
     * Test getNodeCacheInfo
     *
     * @param NodeInterface                                                $node
     * @param OpenOrchestra\DisplayBundle\DisplayBlock\DisplayBlockManager $displayBlockManager
     * @param OpenOrchestra\ModelInterface\Model\BlockInterface            $blockTransverse
     * @param bool                                                         $expectedPublicCache
     * @param int                                                          $expectedMaxage
     *
     * @dataProvider provideNode
     */
    public function testGetNodeCacheInfo(
        $node,
        $displayBlockManager,
        $blockTransverse,
        $expectedPublicCache,
        $expectedMaxage
    ) {
         Phake::when($this->transverseNode)->getBlock(Phake::anyParameters())
             ->thenReturn($blockTransverse);

        $manager = new NodeResponseManager($this->nodeRepository, $displayBlockManager);

        $cacheInfo = $manager->getNodeCacheInfo($node);

        $this->assertSame($expectedPublicCache, $cacheInfo['isPublic']);
        $this->assertSame($expectedMaxage, $cacheInfo['MaxAge']);
    }

    /**
     * provide Nodes
     */
    public function provideNode()
    {
        // Blocks //
        $block1 = Phake::mock('OpenOrchestra\ModelInterface\Model\BlockInterface');
        Phake::when($block1)->getMaxAge()->thenReturn($this->maxAge['block1']);

        $block2 = Phake::mock('OpenOrchestra\ModelInterface\Model\BlockInterface');
        Phake::when($block2)->getMaxAge()->thenReturn($this->maxAge['block2']);

        $block3 = Phake::mock('OpenOrchestra\ModelInterface\Model\BlockInterface');
        Phake::when($block3)->getMaxAge()->thenReturn($this->maxAge['block3']);

        $blockTransverse = Phake::mock('OpenOrchestra\ModelInterface\Model\BlockInterface');
        Phake::when($blockTransverse)->getMaxAge()->thenReturn($this->maxAge['blockTransverse']);

        $blockPrivate = Phake::mock('OpenOrchestra\ModelInterface\Model\BlockInterface');
        Phake::when($blockPrivate)->getMaxAge()->thenReturn($this->maxAge['blockPrivate']);

        // DisplayBlockManager //
        $displayBlockManager = Phake::mock('OpenOrchestra\DisplayBundle\DisplayBlock\DisplayBlockManager');

        Phake::when($displayBlockManager)->isPublic($block1)->thenReturn($this->isPublic['block1']);
        Phake::when($displayBlockManager)->isPublic($block2)->thenReturn($this->isPublic['block2']);
        Phake::when($displayBlockManager)->isPublic($block3)->thenReturn($this->isPublic['block3']);
        Phake::when($displayBlockManager)->isPublic($blockTransverse)->thenReturn($this->isPublic['blockTransverse']);
        Phake::when($displayBlockManager)->isPublic($blockPrivate)->thenReturn($this->isPublic['blockPrivate']);

        // Area 1 //
        $area11 = Phake::mock('OpenOrchestra\ModelInterface\Model\AreaInterface');
        Phake::when($area11)->getBlocks()->thenReturn(array(array('nodeId'=> 0, 'blockId' => 1)));

        $area12 = Phake::mock('OpenOrchestra\ModelInterface\Model\AreaInterface');
        Phake::when($area12)->getBlocks()->thenReturn(array(array('nodeId'=> 0, 'blockId' => 2)));

        $area1 = Phake::mock('OpenOrchestra\ModelInterface\Model\AreaInterface');
        Phake::when($area1)->getAreas()->thenReturn(new ArrayCollection(array($area11, $area12)));

        // Area 2 //
        $area21 = Phake::mock('OpenOrchestra\ModelInterface\Model\AreaInterface');
        Phake::when($area21)->getBlocks()->thenReturn(array());

        $area22 = Phake::mock('OpenOrchestra\ModelInterface\Model\AreaInterface');
        Phake::when($area22)->getBlocks()->thenReturn(
            array(
                array('nodeId'=> 0, 'blockId' => 3),
                array('nodeId'=> 'other-node-id', 'blockId' => 1)
            )
        );

        $area2 = Phake::mock('OpenOrchestra\ModelInterface\Model\AreaInterface');
        Phake::when($area2)->getAreas()->thenReturn(new ArrayCollection(array($area21, $area22)));

        // Area Private //
        $areaPrivate = Phake::mock('OpenOrchestra\ModelInterface\Model\AreaInterface');
        Phake::when($areaPrivate)->getBlocks()
            ->thenReturn(array(array('nodeId'=> 0, 'blockId' => 'Private')));

        /* Public Node
         *  |
         *  |_ Area1
         *  |   |_ Area11
         *  |   |   |_ bref(0:1)
         *  |   |
         *  |   |_ Area12
         *  |       |_ bref(0:2)
         *  |
         *  |_ Area2
         *      |_ Area21
         *      |  |_ empty
         *      |
         *      |_ Area22
         *          |_ bref(0:3)
         *          |_ bref(other-node-id:1)
         */
        $publicNode = Phake::mock('OpenOrchestra\ModelInterface\Model\NodeInterface');
        Phake::when($publicNode)->getNodeId()->thenReturn('public-node-id');
        Phake::when($publicNode)->getMaxAge()->thenReturn($this->maxAge['publicNode']);
        Phake::when($publicNode)->getAreas()->thenReturn(new ArrayCollection(array($area1, $area2)));
        Phake::when($publicNode)->getBlock('1')->thenReturn($block1);
        Phake::when($publicNode)->getBlock('2')->thenReturn($block2);
        Phake::when($publicNode)->getBlock('3')->thenReturn($block3);

        /* Private Node
         *  |
         *  |_ Area1
         *  |   |_ Area11
         *  |   |   |_ bref(0:1)
         *  |   |
         *  |   |_ Area12
         *  |       |_ bref(0:2)
         *  |
         *  |_ AreaPrivate
         *  |   |_ bref(0:Private)
         *  |
         *  |_ Area2
         *      |_ Area21
         *      |  |_ empty
         *      |
         *      |_ Area22
         *          |_ bref(0:3)
         *          |_ bref(other-node-id:1)
         */
        $privateNode = Phake::mock('OpenOrchestra\ModelInterface\Model\NodeInterface');
        Phake::when($privateNode)->getNodeId()->thenReturn('private-node-id');
        Phake::when($privateNode)->getMaxAge()->thenReturn($this->maxAge['privateNode']);
        Phake::when($privateNode)->getAreas()->thenReturn(new ArrayCollection(array($area1, $areaPrivate, $area2)));
        Phake::when($privateNode)->getBlock('1')->thenReturn($block1);
        Phake::when($privateNode)->getBlock('2')->thenReturn($block2);
        Phake::when($privateNode)->getBlock('3')->thenReturn($block3);
        Phake::when($privateNode)->getBlock('Private')->thenReturn($blockPrivate);

        return array(
            array($publicNode, $displayBlockManager, $blockTransverse, true, 12),
            array($privateNode, $displayBlockManager, $blockTransverse, false, 20)
        );
    }
}
