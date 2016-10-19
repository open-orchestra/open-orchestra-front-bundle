<?php

namespace OpenOrchestra\FrontBundle\Manager;

use OpenOrchestra\ModelInterface\Repository\NodeRepositoryInterface;
use OpenOrchestra\ModelInterface\Model\ReadNodeInterface;
use OpenOrchestra\ModelInterface\Model\AreaInterface;
use OpenOrchestra\DisplayBundle\DisplayBlock\DisplayBlockManager;
use Doctrine\Common\Collections\Collection;

/**
 * Class NodeResponseManager
 */
class NodeResponseManager
{
    protected $nodeRepository;
    protected $displayBlockManager;

    /**
     * @param NodeRepositoryInterface $nodeRepository
     * @param DisplayBlockManager     $displayBlockManager
     */
    public function __construct(
        NodeRepositoryInterface $nodeRepository,
        DisplayBlockManager $displayBlockManager
    ) {
        $this->nodeRepository = $nodeRepository;
        $this->displayBlockManager = $displayBlockManager;
    }

    /**
     * Browse the hole $node to calculate the general cache policy of the $node
     * according to the areas and blocks.
     * If a block is private, the hole $node becomes private
     * The Max-age of the node is the min of the Max-age of all blocks
     *
     * @param ReadNodeInterface $node
     *
     * return array
     */
    public function getNodeCacheInfo(ReadNodeInterface $node)
    {
        return $this->getCacheInfoFromAreas($node->getRootArea()->getAreas(), $node, $node->getMaxAge());
    }

    /**
     * Browse the $areaCollection to calculate a minimal cache policy
     * according to the areas and blocks.
     * If a block is private, the result is private
     * The Max-age is the min of the Max-age of all blocks
     *
     * @param Collection        $areaCollection
     * @param ReadNodeInterface $node
     * @param string            $defaultMaxAge
     * @param string            $defaultIsPublic
     *
     * @return array
     */
    protected function getCacheInfoFromAreas(
        Collection $areaCollection,
        ReadNodeInterface $node,
        $defaultMaxAge = -1,
        $defaultIsPublic = true
    ) {
        $cacheInfo = $this->formatCacheInfo($defaultMaxAge, $defaultIsPublic);

        foreach ($areaCollection as $area) {
            $cacheInfo = $this->mergeCacheInfo($cacheInfo, $this->getAreaCacheInfo($area, $node));
        }

        return $cacheInfo;
    }

    /**
     * Browse the $area to calculate the general cache policy of the $area
     * according to the sub-areas and blocks.
     * If a block is private, the $area becomes private
     * The Max-age of the area is the min of the Max-age of all blocks
     *
     * @param AreaInterface     $area
     * @param ReadNodeInterface $node
     *
     * return array
     */
    protected function getAreaCacheInfo(AreaInterface $area, ReadNodeInterface $node)
    {
        if (count($area->getAreas()) > 0 ) {
            $cacheInfo = $this->getCacheInfoFromAreas($area->getAreas(), $node);
        } else {
            $cacheInfo = $this->getCacheInfoFromBlocks($area, $node);
        }

        return $cacheInfo;
    }

    /**
     * Browse the $area to calculate the general cache policy of the $area
     * according to the blocks.
     * If a block is private, the $area becomes private
     * The Max-age of the area is the min of the Max-age of all blocks
     *
     * @param AreaInterface $area
     * @param ReadNodeInterface $node
     *
     * return array
     */
    protected function getCacheInfoFromBlocks(AreaInterface $area, ReadNodeInterface $node)
    {
        $cacheInfo = $this->formatCacheInfo(-1, true);

        foreach ($area->getBlocks() as $refBlock) {
            if (!($node->getNodeId() === $refBlock['nodeId'] || 0 === $refBlock['nodeId'])) {
                $otherNode = $this->nodeRepository->findInLastVersion(
                    $refBlock['nodeId'],
                    $node->getLanguage(),
                    $node->getSiteId()
                );
                $block = $otherNode->getBlock($refBlock['blockId']);
            } else {
                $block = $node->getBlock($refBlock['blockId']);
            }

            $cacheInfo = $this->mergeCacheInfo(
                $cacheInfo,
                $this->formatCacheInfo(
                    $block->getMaxAge(),
                    $this->displayBlockManager->isPublic($block)
                )
            );
        }

        return $cacheInfo;
    }

    /**
     * Generate a CacheInfo
     *
     * @param int|null $MaxAge
     * @param bool     $isPublic
     *
     * @return array
     */
    protected function formatCacheInfo($MaxAge, $isPublic)
    {
        if (is_null($MaxAge)) {
            $MaxAge = 0;
        }

        return array('MaxAge' => $MaxAge, 'isPublic' => $isPublic);
    }

    /**
     * Merge two CacheInfo
     *
     * @param array $cacheInfogetCacheInfoFromAreas1
     * @param array $cacheInfo2
     */
    protected function mergeCacheInfo(array $cacheInfo1, array $cacheInfo2)
    {
        $maxAge = $cacheInfo1['MaxAge'];

        if ($maxAge < 0 || (($cacheInfo2['MaxAge'] < $maxAge)) && (-1 < $cacheInfo2['MaxAge'])) {
            $maxAge = $cacheInfo2['MaxAge'];
        }

        $isPublic = $cacheInfo1['isPublic'] && $cacheInfo2['isPublic'];

        return $this->formatCacheInfo($maxAge, $isPublic);
    }
}
