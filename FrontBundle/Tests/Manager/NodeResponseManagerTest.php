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
    /**
     * Test getNodeCacheInfo
     *
     * @param NodeInterface                                                 $node
     * @param \OpenOrchestra\DisplayBundle\DisplayBlock\DisplayBlockManager $displayBlockManager
     * @param bool                                                          $expectedPublicCache
     * @param int                                                           $expectedMaxage
     *
     * @dataProvider provideNode
     */
    public function testGetNodeCacheInfo(
        $node,
        $displayBlockManager,
        $expectedPublicCache,
        $expectedMaxage
    ) {
        $manager = new NodeResponseManager($displayBlockManager);

        $cacheInfo = $manager->getNodeCacheInfo($node);

        $this->assertSame($expectedPublicCache, $cacheInfo['isPublic']);
        $this->assertSame($expectedMaxage, $cacheInfo['MaxAge']);
    }

    /**
     * provide Nodes
     */
    public function provideNode()
    {
        $maxAge = array(
            'block1' => 75,
            'block2' => 125,
            'block3' => 25,
            'blockTransverse' => 50,
            'blockPrivate' => 20,
            'publicNode' => 12,
            'privateNode' => 500
        );

        $isPublic = array(
            'block1' => true,
            'block2' => true,
            'block3' => true,
            'blockTransverse' => true,
            'blockPrivate' => false
        );

        // Blocks //
        $block1 = Phake::mock('OpenOrchestra\ModelInterface\Model\BlockInterface');
        Phake::when($block1)->getMaxAge()->thenReturn($maxAge['block1']);

        $block2 = Phake::mock('OpenOrchestra\ModelInterface\Model\BlockInterface');
        Phake::when($block2)->getMaxAge()->thenReturn($maxAge['block2']);

        $block3 = Phake::mock('OpenOrchestra\ModelInterface\Model\BlockInterface');
        Phake::when($block3)->getMaxAge()->thenReturn($maxAge['block3']);

        $blockTransverse = Phake::mock('OpenOrchestra\ModelInterface\Model\BlockInterface');
        Phake::when($blockTransverse)->getMaxAge()->thenReturn($maxAge['blockTransverse']);
        Phake::when($blockTransverse)->isTransverse()->thenReturn(true);

        $blockPrivate = Phake::mock('OpenOrchestra\ModelInterface\Model\BlockInterface');
        Phake::when($blockPrivate)->getMaxAge()->thenReturn($maxAge['blockPrivate']);

        // DisplayBlockManager //
        $displayBlockManager = Phake::mock('OpenOrchestra\DisplayBundle\DisplayBlock\DisplayBlockManager');

        Phake::when($displayBlockManager)->isPublic($block1)->thenReturn($isPublic['block1']);
        Phake::when($displayBlockManager)->isPublic($block2)->thenReturn($isPublic['block2']);
        Phake::when($displayBlockManager)->isPublic($block3)->thenReturn($isPublic['block3']);
        Phake::when($displayBlockManager)->isPublic($blockTransverse)->thenReturn($isPublic['blockTransverse']);
        Phake::when($displayBlockManager)->isPublic($blockPrivate)->thenReturn($isPublic['blockPrivate']);


        $area1 = Phake::mock('OpenOrchestra\ModelInterface\Model\AreaInterface');
        Phake::when($area1)->getBlocks()->thenReturn(new ArrayCollection(array($block1, $block2)));

        $area2 = Phake::mock('OpenOrchestra\ModelInterface\Model\AreaInterface');
        Phake::when($area2)->getBlocks()->thenReturn(new ArrayCollection(array($block3, $blockTransverse)));

        // Area Private //
        $areaPrivate = Phake::mock('OpenOrchestra\ModelInterface\Model\AreaInterface');
        Phake::when($areaPrivate)->getBlocks()->thenReturn(new ArrayCollection(array($blockPrivate)));

        /* Public Node
         *  |
         *  |_ Area1
         *  |
         *  |_ Area2
         *  |
         *  |_ AreaPrivate
         *
         */
        $publicNode = Phake::mock('OpenOrchestra\ModelInterface\Model\NodeInterface');
        Phake::when($publicNode)->getNodeId()->thenReturn('public-node-id');
        Phake::when($publicNode)->getMaxAge()->thenReturn($maxAge['publicNode']);
        Phake::when($publicNode)->getAreas()->thenReturn(new ArrayCollection(array($area1, $area2)));

        /* Private Node
        *  |
        *  |_ Area1
        *  |
        *  |_ Area2
        *  |
        *  |_ AreaPrivate
        *
        */
        $privateNode = Phake::mock('OpenOrchestra\ModelInterface\Model\NodeInterface');
        Phake::when($privateNode)->getNodeId()->thenReturn('private-node-id');
        Phake::when($privateNode)->getMaxAge()->thenReturn($maxAge['privateNode']);
        Phake::when($privateNode)->getAreas()->thenReturn(new ArrayCollection(array($area1, $area2, $areaPrivate)));

        return array(
            array($publicNode, $displayBlockManager, true, 12),
            array($privateNode, $displayBlockManager, false, 20)
        );
    }
}
