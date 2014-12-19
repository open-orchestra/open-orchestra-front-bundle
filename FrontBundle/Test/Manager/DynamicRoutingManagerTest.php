<?php

namespace PHPOrchestra\FrontBundle\Test\Manager;

use Phake;
use PHPOrchestra\FrontBundle\Manager\DynamicRoutingManager;
use PHPOrchestra\ModelInterface\Model\NodeInterface;

/**
 * Class DynamicRoutingManagerTest
 */
class DynamicRoutingManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DynamicRoutingManager
     */
    protected $manager;

    protected $defaultLanguage = 'fr';
    protected $siteRepository;
    protected $siteId = "1";
    protected $siteManager;
    protected $repository;
    protected $languages;
    protected $nodeId;
    protected $node;
    protected $site;

    public function setUp()
    {
        $this->languages = array('en', 'fr');
        $this->site = Phake::mock('PHPOrchestra\ModelInterface\Model\SiteInterface');
        Phake::when($this->site)->getDefaultLanguage()->thenReturn($this->defaultLanguage);
        Phake::when($this->site)->getLanguages()->thenReturn($this->languages);

        $this->siteRepository = Phake::mock('PHPOrchestra\ModelInterface\Repository\SiteRepositoryInterface');
        Phake::when($this->siteRepository)->findOneBySiteId(Phake::anyParameters())->thenReturn($this->site);

        $this->siteManager = Phake::mock('PHPOrchestra\BaseBundle\Context\CurrentSiteIdInterface');
        Phake::when($this->siteManager)->getCurrentSiteId()->thenReturn($this->siteId);
        Phake::when($this->siteManager)->getCurrentSiteDefaultLanguage()->thenReturn($this->defaultLanguage);

        $this->nodeId = 'nodeId';
        $this->node = Phake::mock('PHPOrchestra\ModelInterface\Model\NodeInterface');
        Phake::when($this->node)->getNodeId()->thenReturn($this->nodeId);

        $this->repository = Phake::mock('PHPOrchestra\ModelInterface\Repository\NodeRepositoryInterface');
        Phake::when($this->repository)->findOneByParendIdAndAliasAndSiteId(NodeInterface::ROOT_NODE_ID, 'node', $this->siteId)
            ->thenReturn($this->node);
        Phake::when($this->repository)->findOneByParendIdAndAliasAndSiteId('parent', 'node', $this->siteId)->thenReturn($this->node);

        $this->manager = new DynamicRoutingManager($this->repository, $this->siteManager, $this->siteRepository);
    }

    /**
     * @param string $pathInfo
     * @param string $nodeId
     * @param array  $moduleParameters
     * @param string $localeExpected
     *
     * @dataProvider providePathInfo
     */
    public function testGetRouteParameterFromRequestPathInfoWithNoNode($pathInfo, $nodeId, $moduleParameters, $localeExpected = null)
    {
        if (is_null($localeExpected)) {
            $localeExpected = $this->defaultLanguage;
        }

        $parameters = $this->manager->getRouteParameterFromRequestPathInfo($pathInfo);

        $this->assertSame(array(
            "_route" => "php_orchestra_front_node",
            "_controller" => 'PHPOrchestra\FrontBundle\Controller\NodeController::showAction',
            "_locale" => $localeExpected,
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
            array('/en', NodeInterface::ROOT_NODE_ID, array(), 'en'),
            array('/fr', NodeInterface::ROOT_NODE_ID, array()),
            array('/param1', NodeInterface::ROOT_NODE_ID, array(1 => 'param1')),
            array('/en/param1', NodeInterface::ROOT_NODE_ID, array(0 => 'param1'), "en"),
            array('/param1/param2', NodeInterface::ROOT_NODE_ID, array(1 => 'param1', 2 => 'param2')),
            array('/en/param1/param2', NodeInterface::ROOT_NODE_ID, array(0 => 'param1', 1 => 'param2'), 'en'),
            array('/node', $nodeId, array()),
            array('/en/node', $nodeId, array(), 'en'),
            array('/node/param1', $nodeId, array('param1')),
            array('/node/param1/param2', $nodeId, array('param1', 'param2')),
            array('/en/node/param1/param2', $nodeId, array('param1', 'param2'), 'en'),
            array('/parent/node', $nodeId, array()),
            array('/parent/node/param1', $nodeId, array('param1')),
            array('/parent/node/param1/param2', $nodeId, array('param1', 'param2')),
            array('/en/parent/node/param1/param2', $nodeId, array('param1', 'param2'), 'en'),
        );
    }
}
