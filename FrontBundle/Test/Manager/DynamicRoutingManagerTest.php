<?php

namespace PHPOrchestra\FrontBundle\Test\Manager;

use Phake;
use PHPOrchestra\FrontBundle\Manager\DynamicRoutingManager;
use PHPOrchestra\ModelBundle\Model\NodeInterface;

/**
 * Class DynamicRoutingManagerTest
 */
class DynamicRoutingManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DynamicRoutingManager
     */
    protected $manager;

    protected $siteManager;
    protected $repository;
    protected $siteId = "1";
    protected $nodeId;
    protected $node;

    public function setUp()
    {
        $this->siteManager = Phake::mock('PHPOrchestra\DisplayBundle\Manager\SiteManager');
        Phake::when($this->siteManager)->getCurrentSiteId()->thenReturn($this->siteId);

        $this->nodeId = 'nodeId';
        $this->node = Phake::mock('PHPOrchestra\ModelBundle\Model\NodeInterface');
        Phake::when($this->node)->getNodeId()->thenReturn($this->nodeId);

        $this->repository = Phake::mock('PHPOrchestra\ModelBundle\Repository\NodeRepository');
        Phake::when($this->repository)->findOneBy(array(
            'parentId' => NodeInterface::ROOT_NODE_ID,
            'alias' => 'node',
            'siteId' => $this->siteId
        ))->thenReturn($this->node);
        Phake::when($this->repository)->findOneBy(array(
            'parentId' => 'parent',
            'alias' => 'node',
            'siteId' => $this->siteId
        ))->thenReturn($this->node);

        $this->manager = new DynamicRoutingManager($this->repository, $this->siteManager);
    }

    /**
     * @param string $pathInfo
     * @param string $nodeId
     * @param array $moduleParameters
     *
     * @dataProvider providePathInfo
     */
    public function testGetRouteParameterFromRequestPathInfoWithNoNode($pathInfo, $nodeId, $moduleParameters)
    {
        $parameters = $this->manager->getRouteParameterFromRequestPathInfo($pathInfo);

        $this->assertSame(array(
            "_route" => "php_orchestra_front_node",
            "_controller" => 'PHPOrchestra\FrontBundle\Controller\NodeController::showAction',
            "nodeId" => $nodeId,
            "module_parameters" => $moduleParameters
        ), $parameters);
    }

    /**
     * @return array
     */
    public function providePathInfo()
    {
        $nodeId = 'nodeId';

        return array(
            array('/', NodeInterface::ROOT_NODE_ID, array()),
            array('/param1', NodeInterface::ROOT_NODE_ID, array(1 => 'param1')),
            array('/param1/param2', NodeInterface::ROOT_NODE_ID, array(1 => 'param1', 2 => 'param2')),
            array('/node', $nodeId, array()),
            array('/node/param1', $nodeId, array('param1')),
            array('/node/param1/param2', $nodeId, array('param1', 'param2')),
            array('/parent/node', $nodeId, array()),
            array('/parent/node/param1', $nodeId, array('param1')),
            array('/parent/node/param1/param2', $nodeId, array('param1', 'param2')),
        );
    }
}
