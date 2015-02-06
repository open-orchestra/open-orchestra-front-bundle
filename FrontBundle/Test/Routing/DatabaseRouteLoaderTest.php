<?php

namespace PHPOrchestra\FrontBundle\Test\Routing;

use Doctrine\Common\Collections\ArrayCollection;
use Phake;
use PHPOrchestra\FrontBundle\Routing\DatabaseRouteLoader;

/**
 * Test DatabaseRouteLoaderTest
 */
class DatabaseRouteLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DatabaseRouteLoader
     */
    protected $loader;

    protected $resource = '.';
    protected $nodeRepository;
    protected $siteRepository;

    /**
     * Set up the test
     */
    public function setUp()
    {
        $this->siteRepository = Phake::mock('PHPOrchestra\ModelInterface\Repository\SiteRepositoryInterface');
        Phake::when($this->siteRepository)->findByDeleted(Phake::anyParameters())->thenReturn(array());

        $this->nodeRepository = Phake::mock('PHPOrchestra\ModelInterface\Repository\NodeRepositoryInterface');

        $this->loader = new DatabaseRouteLoader($this->nodeRepository, $this->siteRepository);
    }

    /**
     * Test instance
     */
    public function testInstance()
    {
        $this->assertInstanceOf('Symfony\Component\Config\Loader\LoaderInterface', $this->loader);
    }

    /**
     * Test support
     */
    public function testSupport()
    {
        $this->assertTrue($this->loader->supports($this->resource, 'database'));
    }

    /**
     * test exception
     */
    public function testRunOnlyOnce()
    {
        $this->loader->load($this->resource, 'database');
        $this->setExpectedException('RuntimeException');
        $this->loader->load($this->resource, 'database');
    }

    /**
     * Test load routes
     */
    public function testLoad()
    {
        // Define site aliases
        $frdomain = 'frdomain.com';
        $endomain = 'endomain.com';
        $keyFr = 0;
        $keyEn = 1;
        $siteAliasfr = Phake::mock('PHPOrchestra\ModelInterface\Model\SiteAliasInterface');
        Phake::when($siteAliasfr)->getDomain()->thenReturn($frdomain);
        Phake::when($siteAliasfr)->getLanguages()->thenReturn(array('fr'));
        $siteAliasen = Phake::mock('PHPOrchestra\ModelInterface\Model\SiteAliasInterface');
        Phake::when($siteAliasen)->getDomain()->thenReturn($endomain);
        Phake::when($siteAliasen)->getLanguages()->thenReturn(array('en'));
        $siteAliases = new ArrayCollection();
        $siteAliases->set($keyFr, $siteAliasfr);
        $siteAliases->set($keyEn, $siteAliasen);

        // Define site
        $siteId = 'siteId';
        $site = Phake::mock('PHPOrchestra\ModelInterface\Model\SiteInterface');
        Phake::when($site)->getSiteId()->thenReturn($siteId);
        Phake::when($site)->getAliases()->thenReturn($siteAliases);
        Phake::when($site)->getLanguages()->thenReturn(array('en', 'fr'));

        Phake::when($this->siteRepository)->findByDeleted(false)->thenReturn(array($site));

        $nodeId = 'nodeId';
        // Define fr nodes
        $frMongoId = 'frMongoId';
        $frPattern = '/fr/nodeId/{variable}';
        $frNode = Phake::mock('PHPOrchestra\ModelInterface\Model\NodeInterface');
        Phake::when($frNode)->getId()->thenReturn($frMongoId);
        Phake::when($frNode)->getNodeId()->thenReturn($nodeId);
        Phake::when($frNode)->getRoutePattern()->thenReturn($frPattern);
        Phake::when($frNode)->getLanguage()->thenReturn('fr');
        $frNodes = new ArrayCollection();
        $frNodes->add($frNode);

        // Define en nodes
        $enMongoId = 'enMongoId';
        $enPattern = '/en/nodeId/{variable}';
        $enNode = Phake::mock('PHPOrchestra\ModelInterface\Model\NodeInterface');
        Phake::when($enNode)->getId()->thenReturn($enMongoId);
        Phake::when($enNode)->getNodeId()->thenReturn($nodeId);
        Phake::when($enNode)->getRoutePattern()->thenReturn($enPattern);
        Phake::when($enNode)->getLanguage()->thenReturn('en');
        $enNodes = new ArrayCollection();
        $enNodes->add($enNode);

        // Define the repository return
        Phake::when($this->nodeRepository)->findLastPublishedVersionByLanguageAndSiteId('fr', $siteId)->thenReturn($frNodes);
        Phake::when($this->nodeRepository)->findLastPublishedVersionByLanguageAndSiteId('en', $siteId)->thenReturn($enNodes);

        $routeCollection = $this->loader->load($this->resource, 'database');

        $this->assertInstanceOf('Symfony\Component\Routing\RouteCollection', $routeCollection);
        $this->assertCount(2, $routeCollection);

        // Check the fr route
        $frRoute = $routeCollection->get($keyFr . '_' . $frMongoId);
        $this->assertInstanceOf('Symfony\Component\Routing\Route', $frRoute);
        $this->assertSame($frPattern, $frRoute->getPath());
        $this->assertSame($frdomain, $frRoute->getHost());
        $this->assertSame(
            array(
                '_controller' => 'PHPOrchestra\FrontBundle\Controller\NodeController::showAction',
                '_locale' => 'fr',
                'nodeId' => $nodeId,
                'siteId' => $siteId,
                'aliasId' => $keyFr,
            ),
            $frRoute->getDefaults()
        );

        // Check the en route
        $enRoute = $routeCollection->get($keyEn . '_' . $enMongoId);
        $this->assertInstanceOf('Symfony\Component\Routing\Route', $enRoute);
        $this->assertSame($enPattern, $enRoute->getPath());
        $this->assertSame($endomain, $enRoute->getHost());
        $this->assertSame(
            array(
                '_controller' => 'PHPOrchestra\FrontBundle\Controller\NodeController::showAction',
                '_locale' => 'en',
                'nodeId' => $nodeId,
                'siteId' => $siteId,
                'aliasId' => $keyEn,
            ),
            $enRoute->getDefaults()
        );
    }
}
