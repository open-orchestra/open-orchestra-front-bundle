<?php

namespace OpenOrchestra\FrontBundle\Test\Routing;

use Doctrine\Common\Collections\ArrayCollection;
use Phake;
use OpenOrchestra\FrontBundle\Routing\DatabaseRouteLoader;
use OpenOrchestra\ModelInterface\Model\NodeInterface;
use OpenOrchestra\ModelInterface\Model\SiteAliasInterface;

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
        $this->siteRepository = Phake::mock('OpenOrchestra\ModelInterface\Repository\SiteRepositoryInterface');
        Phake::when($this->siteRepository)->findByDeleted(Phake::anyParameters())->thenReturn(array());

        $this->nodeRepository = Phake::mock('OpenOrchestra\ModelInterface\Repository\NodeRepositoryInterface');

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
        $this->assertFalse($this->loader->supports($this->resource, 'redirection'));
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
        $frLocale = 'fr';
        $enLocale = 'en';

        // Define site aliases
        $frdomain = 'frdomain.com';
        $endomain = 'endomain.com';
        $keyFr = 0;
        $keyEn = 1;
        $siteAliasfr = $this->mockSiteAlias($frdomain, $frLocale);
        $siteAliasen = $this->mockSiteAlias($endomain, $enLocale, $enLocale);
        $siteAliases = new ArrayCollection();
        $siteAliases->set($keyFr, $siteAliasfr);
        $siteAliases->set($keyEn, $siteAliasen);

        // Define site
        $siteId = 'siteId';
        $site = Phake::mock('OpenOrchestra\ModelInterface\Model\SiteInterface');
        Phake::when($site)->getSiteId()->thenReturn($siteId);
        Phake::when($site)->getAliases()->thenReturn($siteAliases);
        Phake::when($site)->getLanguages()->thenReturn(array($enLocale, $frLocale));

        Phake::when($this->siteRepository)->findByDeleted(false)->thenReturn(array($site));

        $nodeId = 'nodeId';
        $sonId = 'sonId';
        $grandSonId = 'grandSonId';
        // Define fr nodes
        $frMongoId = 'frMongoId';
        $frPattern = '';
        $frNode = $this->mockNode($frMongoId, $nodeId, $frPattern, $frLocale);
        $frSonMongoId = 'frSonMongoId';
        $frSonPattern = '{variable}';
        $frSonNode = $this->mockNode($frSonMongoId, $sonId, $frSonPattern, $frLocale, $nodeId);
        $frGrandSonMongoId = 'frGrandSonMongoId';
        $frGrandSonPattern = 'blog';
        $frGrandSonNode = $this->mockNode($frGrandSonMongoId, $grandSonId, $frGrandSonPattern, $frLocale, $sonId);
        $frNodes[] = $frNode;
        $frNodes[] = $frSonNode;
        $frNodes[] = $frGrandSonNode;

        // Define en nodes
        $enMongoId = 'enMongoId';
        $enPattern = '';
        $enNode = $this->mockNode($enMongoId, $nodeId, $enPattern, $enLocale);
        $enSonMongoId = 'enSonMongoId';
        $enSonPattern = '{variable}';
        $enSonNode = $this->mockNode($enSonMongoId, $sonId, $enSonPattern, $enLocale, $nodeId);
        $enGrandSonMongoId = 'enGrandSonMongoId';
        $enGrandSonPattern = 'blog';
        $enGrandSonNode = $this->mockNode($enGrandSonMongoId, $grandSonId, $enGrandSonPattern, $enLocale, $sonId);
        $enNodes[] = $enNode;
        $enNodes[] = $enSonNode;
        $enNodes[] = $enGrandSonNode;

        // Define the repository return
        Phake::when($this->nodeRepository)->findLastPublishedVersionByLanguageAndSiteId($frLocale, $siteId)->thenReturn($frNodes);
        Phake::when($this->nodeRepository)->findLastPublishedVersionByLanguageAndSiteId($enLocale, $siteId)->thenReturn($enNodes);

        $routeCollection = $this->loader->load($this->resource, 'database');

        $this->assertInstanceOf('Symfony\Component\Routing\RouteCollection', $routeCollection);
        $this->assertCount(6, $routeCollection);

        // Check the fr route
        $frRoute = $routeCollection->get($keyFr . '_' . $frMongoId);
        $this->assertRoute($frLocale, '/', $frdomain, $nodeId, $siteId, $keyFr, $frRoute);
        $frSonRoute = $routeCollection->get($keyFr . '_' . $frSonMongoId);
        $this->assertRoute($frLocale, '/{variable}', $frdomain, $sonId, $siteId, $keyFr, $frSonRoute);
        $frGrandSonRoute = $routeCollection->get($keyFr . '_' . $frGrandSonMongoId);
        $this->assertRoute($frLocale, '/{variable}/blog', $frdomain, $grandSonId, $siteId, $keyFr, $frGrandSonRoute);

        // Check the en route
        $enRoute = $routeCollection->get($keyEn . '_' . $enMongoId);
        $this->assertRoute($enLocale, '/en/', $endomain, $nodeId, $siteId, $keyEn, $enRoute);
        $enSonRoute = $routeCollection->get($keyEn . '_' . $enSonMongoId);
        $this->assertRoute($enLocale, '/en/{variable}', $endomain, $sonId, $siteId, $keyEn, $enSonRoute);
        $enGrandSonRoute = $routeCollection->get($keyEn . '_' . $enGrandSonMongoId);
        $this->assertRoute($enLocale, '/en/{variable}/blog', $endomain, $grandSonId, $siteId, $keyEn, $enGrandSonRoute);
    }

    /**
     * Test load routes
     */
    public function testLoadWithFullUrl()
    {
        $frLocale = 'fr';

        // Define site aliases
        $frdomain = 'frdomain.com';
        $keyFr = 0;
        $siteAliasfr = $this->mockSiteAlias($frdomain, $frLocale);
        $siteAliases = new ArrayCollection();
        $siteAliases->set($keyFr, $siteAliasfr);

        // Define site
        $siteId = 'siteId';
        $site = Phake::mock('OpenOrchestra\ModelInterface\Model\SiteInterface');
        Phake::when($site)->getSiteId()->thenReturn($siteId);
        Phake::when($site)->getAliases()->thenReturn($siteAliases);
        Phake::when($site)->getLanguages()->thenReturn(array($frLocale));

        Phake::when($this->siteRepository)->findByDeleted(false)->thenReturn(array($site));

        $nodeId = 'nodeId';
        $sonId = 'sonId';
        $grandSonId = 'grandSonId';
        // Define fr nodes
        $frMongoId = 'frMongoId';
        $frPattern = '';
        $frNode = $this->mockNode($frMongoId, $nodeId, $frPattern, $frLocale);
        $frSonMongoId = 'frSonMongoId';
        $frSonPattern = '{variable}';
        $frSonNode = $this->mockNode($frSonMongoId, $sonId, $frSonPattern, $frLocale, $nodeId);
        $frGrandSonMongoId = 'frGrandSonMongoId';
        $frGrandSonPattern = '/full/blog';
        $frGrandSonNode = $this->mockNode($frGrandSonMongoId, $grandSonId, $frGrandSonPattern, $frLocale, $sonId);
        $frNodes[] = $frNode;
        $frNodes[] = $frSonNode;
        $frNodes[] = $frGrandSonNode;

        // Define the repository return
        Phake::when($this->nodeRepository)->findLastPublishedVersionByLanguageAndSiteId($frLocale, $siteId)->thenReturn($frNodes);

        $routeCollection = $this->loader->load($this->resource, 'database');

        $this->assertInstanceOf('Symfony\Component\Routing\RouteCollection', $routeCollection);
        $this->assertCount(3, $routeCollection);

        // Check the fr route
        $frRoute = $routeCollection->get($keyFr . '_' . $frMongoId);
        $this->assertRoute($frLocale, '/', $frdomain, $nodeId, $siteId, $keyFr, $frRoute);
        $frSonRoute = $routeCollection->get($keyFr . '_' . $frSonMongoId);
        $this->assertRoute($frLocale, '/{variable}', $frdomain, $sonId, $siteId, $keyFr, $frSonRoute);
        $frGrandSonRoute = $routeCollection->get($keyFr . '_' . $frGrandSonMongoId);
        $this->assertRoute($frLocale, '/full/blog', $frdomain, $grandSonId, $siteId, $keyFr, $frGrandSonRoute);
    }

    /**
     * @param $pattern
     * @param $domain
     * @param $nodeId
     * @param $siteId
     * @param $key
     * @param $route
     */
    protected function assertRoute($locale, $pattern, $domain, $nodeId, $siteId, $key, $route)
    {
        $this->assertInstanceOf('Symfony\Component\Routing\Route', $route);
        $this->assertSame($pattern, $route->getPath());
        $this->assertSame($domain, $route->getHost());
        $this->assertSame(
            array(
                '_controller' => 'OpenOrchestra\FrontBundle\Controller\NodeController::showAction',
                '_locale' => $locale,
                'nodeId' => $nodeId,
                'siteId' => $siteId,
                'aliasId' => $key,
            ),
            $route->getDefaults(),
            'http'
        );
    }

    /**
     * @param string $domain
     * @param string $locale
     * @param string $prefix
     *
     * @return SiteAliasInterface
     */
    protected function mockSiteAlias($domain, $locale, $prefix = null)
    {
        $siteAlias = Phake::mock('OpenOrchestra\ModelInterface\Model\SiteAliasInterface');
        Phake::when($siteAlias)->getDomain()->thenReturn($domain);
        Phake::when($siteAlias)->getLanguage()->thenReturn($locale);
        Phake::when($siteAlias)->getPrefix()->thenReturn($prefix);
        Phake::when($siteAlias)->getScheme()->thenReturn('http');

        return $siteAlias;
    }

    /**
     * @param string      $mongoId
     * @param string      $nodeId
     * @param string      $pattern
     * @param string      $locale
     * @param string|null $parentId
     *
     * @return NodeInterface
     */
    protected function mockNode($mongoId, $nodeId, $pattern, $locale, $parentId = null)
    {
        $node = Phake::mock('OpenOrchestra\ModelInterface\Model\NodeInterface');
        Phake::when($node)->getId()->thenReturn($mongoId);
        Phake::when($node)->getNodeId()->thenReturn($nodeId);
        Phake::when($node)->getRoutePattern()->thenReturn($pattern);
        Phake::when($node)->getLanguage()->thenReturn($locale);
        Phake::when($node)->getParentId()->thenReturn($parentId);

        return $node;
    }
}
