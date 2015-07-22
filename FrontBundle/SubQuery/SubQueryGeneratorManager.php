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
     * @param array $blockParameters
     * @param array $baseSubQuery
     *
     * @return array
     */
    public function generate(array $blockParameters, array $baseSubQuery)
    {
        $subQuery = $baseSubQuery;
        /** @var SubQueryGeneratorInterface $strategy */
        foreach ($this->strategies as $strategy) {
            foreach ($blockParameters as $blockParameter) {
                if ($strategy->support($blockParameter)) {
                    $subQuery = array_merge($subQuery, $strategy->generate($blockParameter));
                }
            }
        }

        return $subQuery;
    }
}
