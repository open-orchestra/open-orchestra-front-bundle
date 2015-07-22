<?php

namespace OpenOrchestra\FrontBundle\SubQuery\Strategies;


/**
 * Class DeviceSubQueryStrategy
 */
class DeviceSubQueryStrategy extends AbstractRequestSubQueryStrategy
{
    /**
     * @param string $blockParameter
     *
     * @return bool
     */
    public function support($blockParameter)
    {
        return 'device' === $blockParameter;
    }

    /**
     * @param string $blockParameter
     *
     * @return array
     */
    public function generate($blockParameter)
    {
        if ($device = $this->request->headers->get('x-ua-device')) {
            return array('x-ua-device' => $device);
        }

        return array();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'device';
    }
}
