<?php

namespace OpenOrchestra\FrontBundle\SubQuery;

/**
 * Class SubQueryGeneratorManager
 */
class SubQueryGeneratorManager
{
    protected $strategies = array();

    /**
     * @param SubQueryGeneratorInterface $strategy
     */
    public function addStrategy(SubQueryGeneratorInterface $strategy)
    {
        $this->strategies[$strategy->getName()] = $strategy;
    }

    /**
     * @param array $block
     * @param array $baseSubQuery
     *
     * @return array
     */
    public function generate(array $block, array $baseSubQuery)
    {
        $subQuery = array_merge($baseSubQuery, $this->generateSubQueryCache($block));
        if (isset($block['blockParameter'])) {
            /** @var SubQueryGeneratorInterface $strategy */
            foreach ($this->strategies as $strategy) {
                foreach ($block['blockParameter'] as $blockParameter) {
                    if ($strategy->support($blockParameter)) {
                        $subQuery = array_merge($subQuery, $strategy->generate($blockParameter));
                    }
                }
            }
        }

        return $subQuery;
    }

    /**
     * @param $block
     *
     * @return array
     */
    public function generateSubQueryCache($block)
    {
        $cacheQuery = array();
        if (isset($block['blockPrivate']) && true === $block['blockPrivate']) {
            $cacheQuery = array('cache' => 'private');
        }

        return $cacheQuery;
    }
}
