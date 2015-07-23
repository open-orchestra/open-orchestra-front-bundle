<?php

namespace OpenOrchestra\FrontBundle\SubQuery;

/**
 * Interface SubQueryGeneratorInterface
 */
interface SubQueryGeneratorInterface
{
    /**
     * @param string $blockParameter
     *
     * @return bool
     */
    public function support($blockParameter);

    /**
     * @param string $blockParameter
     *
     * @return array
     */
    public function generate($blockParameter);

    /**
     * @return string
     */
    public function getName();
}
