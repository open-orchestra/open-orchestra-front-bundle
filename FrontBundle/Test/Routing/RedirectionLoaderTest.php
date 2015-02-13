<?php

namespace PHPOrchestra\FrontBundle\Test\Routing;

use Doctrine\Common\Collections\ArrayCollection;
use Phake;
use PHPOrchestra\FrontBundle\Routing\RedirectionLoader;

/**
 * Test RedirectionLoaderTest
 */
class RedirectionLoaderTest extends \PHPUnit_Framework_TestCase
{
    const REDIRECT = 'redirect';
    const URL_REDIRECT = 'urlRedirect';

    /**
     * @var RedirectionLoader
     */
    protected $loader;

    protected $resource = '.';
    protected $nodeRepository;
    protected $siteRepository;
    protected $localeEn = 'en';
    protected $localeFr = 'fr';
    protected $redirectionRepository;
    protected $domainEn = 'domain.en';
    protected $domainFr1 = 'domain1.fr';
    protected $domainFr2 = 'domain2.fr';

    /**
     * Set up the test
     */
    public function setUp()
    {
        $siteAliasFr1 = Phake::mock('PHPOrchestra\ModelInterface\Model\SiteAliasInterface');
        Phake::when($siteAliasFr1)->getLanguage()->thenReturn($this->localeFr);
        Phake::when($siteAliasFr1)->getDomain()->thenReturn($this->domainFr1);
        $siteAliasFr2 = Phake::mock('PHPOrchestra\ModelInterface\Model\SiteAliasInterface');
        Phake::when($siteAliasFr2)->getLanguage()->thenReturn($this->localeFr);
        Phake::when($siteAliasFr2)->getDomain()->thenReturn($this->domainFr2);
        $siteAliasEn = Phake::mock('PHPOrchestra\ModelInterface\Model\SiteAliasInterface');
        Phake::when($siteAliasEn)->getLanguage()->thenReturn($this->localeEn);
        Phake::when($siteAliasEn)->getDomain()->thenReturn($this->domainEn);

        $siteAliases = new ArrayCollection(array($siteAliasFr1, $siteAliasFr2, $siteAliasEn));

        $site = Phake::mock('PHPOrchestra\ModelInterface\Model\SiteInterface');
        Phake::when($site)->getAliases()->thenReturn($siteAliases);

        $this->siteRepository = Phake::mock('PHPOrchestra\ModelInterface\Repository\SiteRepositoryInterface');
        Phake::when($this->siteRepository)->findOneBySiteId(Phake::anyParameters())->thenReturn($site);

        $this->nodeRepository = Phake::mock('PHPOrchestra\ModelInterface\Repository\NodeRepositoryInterface');
        $this->redirectionRepository = Phake::mock('PHPOrchestra\ModelInterface\Repository\RedirectionRepositoryInterface');

        $this->loader = new RedirectionLoader($this->redirectionRepository, $this->nodeRepository, $this->siteRepository);
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
        $this->assertTrue($this->loader->supports($this->resource, 'orchestra_redirection'));
        $this->assertFalse($this->loader->supports($this->resource, 'database'));
    }

    /**
     * Test load with redirection to a node
     */
    public function testLoadWithRedirectionNode()
    {
        $redirectionMongoId = 'redirectionMongoId';
        $nodeMongoId = 'nodeMongoId';
        $siteId = '1';
        $pattern = '/news/welcome';
        $nodeId = 'nodeId';
        $permanent = true;

        // Define the redirection
        $redirection = Phake::mock('PHPOrchestra\ModelInterface\Model\RedirectionInterface');
        Phake::when($redirection)->getId()->thenReturn($redirectionMongoId);
        Phake::when($redirection)->getSiteId()->thenReturn($siteId);
        Phake::when($redirection)->getLocale()->thenReturn($this->localeFr);
        Phake::when($redirection)->getRoutePattern()->thenReturn($pattern);
        Phake::when($redirection)->getNodeId()->thenReturn($nodeId);
        Phake::when($redirection)->isPermanent()->thenReturn($permanent);

        Phake::when($this->redirectionRepository)->findAll()->thenReturn(array($redirection));

        // Define the node
        $node = Phake::mock('PHPOrchestra\ModelInterface\Model\NodeInterface');
        Phake::when($node)->getId()->thenReturn($nodeMongoId);
        Phake::when($node)->getLanguage()->thenReturn($this->localeFr);

        Phake::when($this->nodeRepository)->findOneByNodeIdAndLanguageWithPublishedAndLastVersionAndSiteId($nodeId, $this->localeFr, $siteId)->thenReturn($node);

        $routes = $this->loader->load($this->resource);

        $this->assertInstanceOf('Symfony\Component\Routing\RouteCollection', $routes);
        $this->assertCount(2, $routes);
        $this->assertRoute($pattern, $this->domainFr1, '0_' . $nodeMongoId, $permanent, self::REDIRECT, $routes->get('0_' . $redirectionMongoId));
        $this->assertRoute($pattern, $this->domainFr2, '1_' . $nodeMongoId, $permanent, self::REDIRECT, $routes->get('1_' . $redirectionMongoId));
    }

    /**
     * Test load with redirection to a node
     */
    public function testLoadWithRedirectionUrl()
    {
        $redirectionMongoId = 'redirectionMongoId';
        $siteId = '1';
        $pattern = '/news/welcome';
        $url = '/url';
        $permanent = true;

        // Define the redirection
        $redirection = Phake::mock('PHPOrchestra\ModelInterface\Model\RedirectionInterface');
        Phake::when($redirection)->getId()->thenReturn($redirectionMongoId);
        Phake::when($redirection)->getSiteId()->thenReturn($siteId);
        Phake::when($redirection)->getLocale()->thenReturn($this->localeFr);
        Phake::when($redirection)->getRoutePattern()->thenReturn($pattern);
        Phake::when($redirection)->getUrl()->thenReturn($url);
        Phake::when($redirection)->isPermanent()->thenReturn($permanent);

        Phake::when($this->redirectionRepository)->findAll()->thenReturn(array($redirection));

        $routes = $this->loader->load($this->resource);

        $this->assertInstanceOf('Symfony\Component\Routing\RouteCollection', $routes);
        $this->assertCount(2, $routes);
        $this->assertRoute($pattern, $this->domainFr1, $url, $permanent, self::URL_REDIRECT, $routes->get('0_' . $redirectionMongoId));
        $this->assertRoute($pattern, $this->domainFr2, $url, $permanent, self::URL_REDIRECT, $routes->get('1_' . $redirectionMongoId));
    }

    /**
     * @param $pattern
     * @param $domain
     * @param $routeParam
     * @param $permanent
     * @param $route
     */
    protected function assertRoute($pattern, $domain, $routeParam, $permanent, $redirectType, $route)
    {
        $key = ($redirectType == self::REDIRECT)? 'route': 'path';

        $this->assertInstanceOf('Symfony\Component\Routing\Route', $route);
        $this->assertSame($pattern, $route->getPath());
        $this->assertSame($domain, $route->getHost());
        $this->assertSame(
            array(
                '_controller' => 'FrameworkBundle:Redirect:' . $redirectType ,
                $key => $routeParam,
                'permanent' => $permanent,
            ),
            $route->getDefaults()
        );
    }
}
