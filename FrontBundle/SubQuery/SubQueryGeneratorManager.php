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
     * @param array   $blockParameters
     * @param boolean $blockPrivate
     * @param array   $baseSubQuery
     *
     * @return array
     */
    public function generate(array $blockParameters, $blockPrivate, array $baseSubQuery)
    {
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
