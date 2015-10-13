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
        return array(
            'currentRouteName' => $this->extractRouteName($this->request->get('_route')),
            'aliasId' => $this->request->get('aliasId'),
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'current_route';
    }

    /**
     * @param string $routeName
     *
     * @return string
     */
    protected function extractRouteName($routeName)
    {
        $route = explode('_', $routeName);
        $routeName = end($route);

        return $routeName;
    }
}
