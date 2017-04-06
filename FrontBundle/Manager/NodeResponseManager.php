<?php

namespace OpenOrchestra\FrontBundle\Manager;

use OpenOrchestra\ModelInterface\Model\ReadNodeInterface;
use OpenOrchestra\ModelInterface\Model\AreaInterface;
use OpenOrchestra\DisplayBundle\DisplayBlock\DisplayBlockManager;
use Doctrine\Common\Collections\Collection;

/**
 * Class NodeResponseManager
 */
class NodeResponseManager
{
    const MAX_AGE = 'MaxAge';
    const IS_PUBLIC = 'isPublic';

    protected $displayBlockManager;

    /**
     * @param DisplayBlockManager     $displayBlockManager
     */
    public function __construct(DisplayBlockManager $displayBlockManager) {
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
     * @return array
     */
    public function getNodeCacheInfo(ReadNodeInterface $node)
    {
        return $this->getCacheInfoFromAreas($node->getAreas(), $node->getMaxAge());
    }

    /**
     * Browse the $areaCollection to calculate a minimal cache policy
     * according to the areas and blocks.
     * If a block is private, the result is private
     * The Max-age is the min of the Max-age of all blocks
     *
     * @param Collection        $areas
     * @param integer           $defaultMaxAge
     * @param boolean           $defaultIsPublic
     *
     * @return array
     */
    protected function getCacheInfoFromAreas(
        Collection $areas,
        $defaultMaxAge = -1,
        $defaultIsPublic = true
    ) {
        $cacheInfo = $this->formatCacheInfo($defaultMaxAge, $defaultIsPublic);

        foreach ($areas as $area) {
            $cacheInfo = $this->mergeCacheInfo($cacheInfo, $this->getCacheInfoFromBlocks($area));
        }

        return $cacheInfo;
    }

    /**
     * Browse the $area to calculate the general cache policy of the $area
     * according to the blocks.
     * If a block is private, the $area becomes private
     * The Max-age of the area is the min of the Max-age of all blocks
     *
     * @param AreaInterface     $area
     *
     * @return array
     */
    protected function getCacheInfoFromBlocks(AreaInterface $area)
    {
        $cacheInfo = $this->formatCacheInfo(-1, true);

        foreach ($area->getBlocks() as $block) {
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

        return array(self::MAX_AGE => $MaxAge, self::IS_PUBLIC => $isPublic);
    }

    /**
     * Merge two CacheInfo
     *
     * @param array $cacheInfo1
     * @param array $cacheInfo2
     *
     * @return array
     */
    protected function mergeCacheInfo(array $cacheInfo1, array $cacheInfo2)
    {
        $maxAge = $cacheInfo1[self::MAX_AGE];

        if ($maxAge < 0 || (($cacheInfo2[self::MAX_AGE] < $maxAge)) && (-1 < $cacheInfo2[self::MAX_AGE])) {
            $maxAge = $cacheInfo2[self::MAX_AGE];
        }

        $isPublic = $cacheInfo1[self::IS_PUBLIC] && $cacheInfo2[self::IS_PUBLIC];

        return $this->formatCacheInfo($maxAge, $isPublic);
    }
}
