<?php

namespace OpenOrchestra\FrontBundle\Tests\Manager;

use OpenOrchestra\FrontBundle\Manager\ParametersManager;
use Phake;

/**
 * Class Test
 */
class ParametersManagerTest extends \PHPUnit_Framework_TestCase
{
    protected $parametersManager;
    protected $parameterBag;
    protected $headerBag;
    protected $request;
    protected $node;

    /**
     * Set Up the test
     */
    public function setUp()
    {
        $this->node = Phake::mock('OpenOrchestra\ModelInterface\Model\ReadNodeInterface');
        $this->parameterBag = Phake::mock('Symfony\Component\HttpFoundation\ParameterBag');
        $this->headerBag = Phake::mock('Symfony\Component\HttpFoundation\HeaderBag');
        $this->request = Phake::mock('Symfony\Component\HttpFoundation\Request');
        $this->request->attributes = $this->parameterBag;
        $this->request->headers = $this->headerBag;

        $this->parametersManager = new ParametersManager();
    }

    /**
     * @param string $language
     * @param string $siteId
     * @param string $device
     * @param array  $attributes
     *
     * @dataProvider generateParameters
     */
    public function testGetParameters($language, $siteId, $device, $attributes)
    {
        Phake::when($this->node)->getLanguage()->ThenReturn($language);
        Phake::when($this->node)->getSiteId()->ThenReturn($siteId);
        Phake::when($this->headerBag)->get('x-ua-device')->ThenReturn($device);
        Phake::when($this->parameterBag)->all()->ThenReturn($attributes);

        $expected = array_merge($attributes, array(
            'siteId' => $siteId,
            'language' => $language,
            'x-ua-device' => $device,
        ));

        $result = $this->parametersManager->getParameters($this->request, $this->node);

        $this->assertSame($expected, $result);
    }

    /**
     * @return array
     */
    public function generateParameters()
    {
        return array(
            array('fr', 'demoSite', 'android', array()),
            array('fr', 'demoSite', null, array('_controller' => 'OpenOrchestra\FrontBundle\Controller\NodeController::showAction', '_locale' => 'fr', '_route' => '2_552e2d3802b')),
            array('fr', 'demoSite', '', array('_controller' => 'OpenOrchestra\FrontBundle\Controller\NodeController::showAction', '_locale' => 'fr', '_route' => '2_552e2d3802b', '_route_params' => array('_locale' => 'fr', 'nodeId' => 'root', 'siteId' => 'demoSite'))),
            array('en', 'site', 'ios', array('_controller' => 'OpenOrchestra\FrontBundle\Controller\NodeController::showAction', '_locale' => 'fr', 'siteId' => 'site2','_route' => '2_552e2d3802b', '_route_params' => array('_locale' => 'fr', 'nodeId' => 'root', 'siteId' => 'demoSite'))),
        );
    }

}
 