<?php

namespace OpenOrchestra\FrontBundle\SubQuery\Strategies;

/**
 * Class RequestSubQueryStrategy
 */
class RequestSubQueryStrategy extends AbstractRequestSubQueryStrategy
{
    /**
     * @param string $blockParameter
     *
     * @return bool
     */
    public function support($blockParameter)
    {
        return strpos($blockParameter, 'request.') === 0;
    }

    /**
     * @param string $blockParameter
     *
     * @return array
     */
    public function generate($blockParameter)
    {
        $parameter = str_replace('request.', '', $blockParameter);
        $request = $this->requestStack->getMasterRequest();

        return array($parameter => $request->get($parameter));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'request';
    }
}
