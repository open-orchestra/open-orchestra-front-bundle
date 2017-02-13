<?php

namespace OpenOrchestra\FrontBundle\SubQuery;

use OpenOrchestra\DisplayBundle\DisplayBlock\DisplayBlockManager;
use OpenOrchestra\ModelInterface\Model\ReadBlockInterface;

/**
 * Class SubQueryGeneratorManager
 */
class SubQueryGeneratorManager
{
    protected $displayBlockManager;

    /**
     * @param DisplayBlockManager $displayBlockManager
     */
    public function __construct(DisplayBlockManager $displayBlockManager)
    {
        $this->displayBlockManager = $displayBlockManager;
    }

    protected $strategies = array();

    /**
     * @param SubQueryGeneratorInterface $strategy
     */
    public function addStrategy(SubQueryGeneratorInterface $strategy)
    {
        $this->strategies[$strategy->getName()] = $strategy;
    }

    /**
     * @param ReadBlockInterface $block
     * @param array              $baseSubQuery
     *
     * @return array
     */
    public function generate(ReadBlockInterface $block, array $baseSubQuery)
    {
        $blockPrivate = !$this->displayBlockManager->isPublic($block);
        $blockParameters = $this->displayBlockManager->getBlockParameter($block);
        $subQuery = array_merge($baseSubQuery, $this->generateSubQueryCache($blockPrivate));

        if (!empty($blockParameters)) {
            /** @var SubQueryGeneratorInterface $strategy */
            foreach ($this->strategies as $strategy) {
                foreach ($blockParameters as $blockParameter) {
                    if ($strategy->support($blockParameter)) {
                        $subQuery = array_merge($subQuery, $strategy->generate($blockParameter));
                    }
                }
            }
        }

        return $subQuery;
    }

    /**
     * @param boolean $blockPrivate
     *
     * @return array
     */
    public function generateSubQueryCache($blockPrivate)
    {
        $cacheQuery = array();
        if (true === $blockPrivate) {
            $cacheQuery = array('cache' => 'private');
        }

        return $cacheQuery;
    }
}
