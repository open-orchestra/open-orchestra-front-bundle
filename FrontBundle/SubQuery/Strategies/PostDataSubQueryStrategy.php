<?php

namespace OpenOrchestra\FrontBundle\SubQuery\Strategies;

/**
 * Class PostDataSubQueryStrategy
 */
class PostDataSubQueryStrategy extends AbstractRequestSubQueryStrategy
{
    /**
     * @param string $blockParameter
     *
     * @return bool
     */
    public function support($blockParameter)
    {
        return 'post_data' === $blockParameter;
    }

    /**
     * @param string $blockParameter
     *
     * @return array
     */
    public function generate($blockParameter)
    {
        $request = $this->requestStack->getMasterRequest();

        return $request->request->all();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'post_data';
    }
}
