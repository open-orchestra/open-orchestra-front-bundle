<?php

namespace OpenOrchestra\FrontBundle\SubQuery\Strategies;

use Symfony\Component\HttpFoundation\RequestStack;


/**
 * Class DeviceSubQueryStrategy
 */
class DeviceSubQueryStrategy extends AbstractRequestSubQueryStrategy
{
    /** @var  string */
    private $deviceTypeField;

    /**
     * @param RequestStack $requestStack
     * @param string       $deviceTypeField
     */
    public function __construct(RequestStack $requestStack, $deviceTypeField = 'x-ua-device')
    {
        parent::__construct($requestStack);

        $this->deviceTypeField = $deviceTypeField;
    }

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
        $request = $this->requestStack->getMasterRequest();

        if ($device = $request->headers->get($this->deviceTypeField)) {
            return array($this->deviceTypeField => $device);
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
