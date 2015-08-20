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
        return strpos($blockParameter, 'current_route') === 0;
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
        return 'request';
    }
}
