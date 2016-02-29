<?php

namespace OpenOrchestra\FrontBundle\Tests\Manager;

use OpenOrchestra\BaseBundle\Tests\AbstractTest\AbstractBaseTestCase;
use Phake;
use OpenOrchestra\FrontBundle\Manager\SitemapManager;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class SitemapManagerTest
 */
class SitemapManagerTest extends AbstractBaseTestCase
{
    protected $nodeRepository;
    protected $node;
    protected $nodeCollection;
    protected $router;
    protected $serializer;
    protected $filesystem;
    protected $sitemapManager;

    protected $xmlContent = 'xml';
    protected $siteDomain = 'domain';
    protected $siteId = 'siteId';
    protected $updatedDate;
    protected $changeFreq = 'frequency';
    protected $priority = 'priority';
    protected $prefix = 'fakePrefix';
    protected $domain = 'fakeDomain';

    protected $mapArray;

    /**
     * Set up the test
     */
    public function setUp()
    {
        $this->updatedDate = new \DateTime();
        $this->nodeRepository = Phake::mock('OpenOrchestra\ModelInterface\Repository\NodeRepositoryInterface');
        $this->node = Phake::mock('OpenOrchestra\ModelInterface\Model\ReadNodeInterface');
        Phake::when($this->node)->getUpdatedAt()->thenReturn($this->updatedDate);
        Phake::when($this->node)->getSitemapChangefreq()->thenReturn($this->changeFreq);
        Phake::when($this->node)->getSitemapPriority()->thenReturn($this->priority);
        $this->nodeCollection = new ArrayCollection();
        $this->nodeCollection->add($this->node);
        Phake::when($this->nodeRepository)->findCurrentlyPublishedVersion(Phake::anyParameters())->thenReturn($this->nodeCollection);

        $this->router = Phake::mock('Symfony\Component\Routing\Generator\UrlGeneratorInterface');
        Phake::when($this->router)->generate(Phake::anyParameters())->thenReturn($this->domain.'/'.$this->prefix);

        $this->serializer = Phake::mock('Symfony\Component\Serializer\SerializerInterface');
        Phake::when($this->serializer)->serialize(Phake::anyParameters())->thenReturn($this->xmlContent);

        $this->filesystem = Phake::mock('Symfony\Component\Filesystem\Filesystem');

        $this->sitemapManager = new SitemapManager(
            $this->nodeRepository,
            $this->router,
            $this->serializer,
            $this->filesystem
        );
        $this->mapArray = array(
            'url' => array(
                array(
                    'loc' => $this->domain.'/'.$this->prefix,
                    'lastmod' => $this->updatedDate->format('Y-m-d'),
                    'changefreq' => $this->changeFreq,
                    'priority' => $this->priority
                )
            )
        );
    }

    /**
     * Test generateSitemap
     */
    public function testGenerateSitemap()
    {
        $alias = Phake::mock('OpenOrchestra\ModelInterface\Model\ReadSiteAliasInterface');

        $site = Phake::mock('OpenOrchestra\ModelInterface\Model\ReadSiteInterface');
        Phake::when($site)->getSiteId()->thenReturn($this->siteId);

        Phake::when($site)->getMainAlias()->thenReturn($alias);

        $this->sitemapManager->generateSitemap($site);

        Phake::verify($this->serializer, Phake::times(1))->serialize($this->mapArray, 'xml');
        Phake::verify($this->filesystem, Phake::times(1))->dumpFile('web/' . $this->siteId . '/sitemap.xml', $this->xmlContent);
    }
}
