<?php

namespace OpenOrchestra\FrontBundle\Manager;

use OpenOrchestra\ModelInterface\Model\ReadNodeInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ParametersManager
 */
class ParametersManager
{
    /**
     * @param Request           $request
     * @param ReadNodeInterface $node
     *
     * @return array
     */
    public function getParameters(Request $request, ReadNodeInterface $node)
    {
        return array_merge(
            $request->attributes->all(),
            array('siteId' => $node->getSiteId(), 'language' => $node->getLanguage(), 'x-ua-device' => $request->headers->get('x-ua-device'))
        );
    }
}
