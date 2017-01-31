<?php

namespace OpenOrchestra\FrontBundle\Tests\Manager;

use OpenOrchestra\BaseBundle\Tests\AbstractTest\AbstractBaseTestCase;
use OpenOrchestra\FrontBundle\Manager\NodeManager;
use Phake;

/**
 * Test NodeManagerTest
 */
class NodeManagerTest extends AbstractBaseTestCase
{
    /**
     * @var NodeManager
     */
    protected $manager;

    protected $nodeRepository;
    protected $siteRepository;
    protected $currentSiteManager;

    /**
     * Set up the test
     */
    public function setUp()
    {
        $this->nodeRepository = Phake::mock('OpenOrchestra\ModelInterface\Repository\ReadNodeRepositoryInterface');
        $this->siteRepository = Phake::mock('OpenOrchestra\ModelInterface\Repository\ReadSiteRepositoryInterface');
        $this->currentSiteManager = Phake::mock('OpenOrchestra\BaseBundle\Context\CurrentSiteIdInterface');

        $this->manager = new NodeManager($this->nodeRepository, $this->siteRepository, $this->currentSiteManager);
    }

    /**
     * @param string $nodeId
     * @param string $language
     * @param string $mongoNodeId
     * @param string $siteId
     * @param int    $aliasId
     *
     * @dataProvider provideDataForRouteGeneration
     */
    public function testGetNodeRouteName($nodeId, $language, $mongoNodeId, $siteId, $aliasId)
    {
        Phake::when($this->currentSiteManager)->getCurrentSiteId()->thenReturn($siteId);

        $node = Phake::mock('OpenOrchestra\ModelInterface\Model\ReadNodeInterface');
        Phake::when($node)->getId()->thenReturn($mongoNodeId);
        Phake::when($this->nodeRepository)
            ->findOnePublished(Phake::anyParameters())
            ->thenReturn($node);

        $site = Phake::mock('OpenOrchestra\ModelInterface\Model\ReadSiteInterface');
        Phake::when($site)->getAliasIdForLanguage(Phake::anyParameters())->thenReturn($aliasId);
        Phake::when($this->siteRepository)->findOneBySiteId(Phake::anyParameters())->thenReturn($site);

        $generatedRoute = $this->manager->getNodeRouteName($nodeId, $language);

        $this->assertSame($aliasId . '_' . $mongoNodeId, $generatedRoute);
    }

    /**
     * @return array
     */
    public function provideDataForRouteGeneration()
    {
        return array(
            array('foo', 'en', 'fooMongoId', '1', 2),
            array('foo', 'fr', 'fooMongoId', '1', 3),
        );
    }

    public function testGetNodeRouteNameWithNoPublishedNode()
    {
        $this->setExpectedException('OpenOrchestra\DisplayBundle\Exception\NodeNotFoundException');

        $this->manager->getNodeRouteName('foo', 'en');
    }
}
