<?php

namespace OpenOrchestra\FrontBundle\SubQuery\Strategies;

/**
 * Class CurrentRouteSubQueryStrategy
 */
class CurrentRouteSubQueryStrategy extends AbstractRequestSubQueryStrategy
{
    /**
     * @param string $blockParameter
     *
     * @return bool
     */
    public function support($blockParameter)
    {
        return 'current_route' === $blockParameter;
    }

    /**
     * @param string $blockParameter
     *
     * @return array
     */
    public function generate($blockParameter)
    {
        return array('currentRouteName' => $this->request->get('_route'));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'current_route';
    }
}
