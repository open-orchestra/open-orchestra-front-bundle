<?php

namespace PHPOrchestra\FrontBundle\Test\Manager;

use Phake;
use PHPOrchestra\FrontBundle\Manager\SitemapManager;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class SitemapManagerTest
 */
class SitemapManagerTest extends \PHPUnit_Framework_TestCase
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
    protected $updatedDate;
    protected $changeFreq = 'frequency';
    protected $priority = 'priority';

    protected $mapArray;

    /**
     * Set up the test
     */
    public function setUp()
    {
        $this->updatedDate = new \DateTime();
        $this->nodeRepository = Phake::mock('PHPOrchestra\ModelBundle\Repository\NodeRepository');
        $this->node = Phake::mock('PHPOrchestra\ModelInterface\Model\NodeInterface');
        Phake::when($this->node)->getUpdatedAt()->thenReturn($this->updatedDate);
        Phake::when($this->node)->getSitemapChangefreq()->thenReturn($this->changeFreq);
        Phake::when($this->node)->getSitemapPriority()->thenReturn($this->priority);
        $this->nodeCollection = new ArrayCollection();
        $this->nodeCollection->add($this->node);
        Phake::when($this->nodeRepository)->findLastVersionBySiteId(Phake::anyParameters())->thenReturn($this->nodeCollection);

        $this->router = Phake::mock('Symfony\Component\Routing\Generator\UrlGeneratorInterface');

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
                    'loc' => $this->siteDomain,
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
        $this->markTestSkipped();
        $site = Phake::mock('PHPOrchestra\ModelInterface\Model\SiteInterface');
        Phake::when($site)->getName()->thenReturn($this->siteDomain);

        $this->sitemapManager->generateSitemap($site);

        Phake::verify($this->serializer, Phake::times(1))->serialize($this->mapArray, 'xml');
        Phake::verify($this->filesystem, Phake::times(1))->dumpFile('web/sitemap.' . $this->siteDomain . '.xml', $this->xmlContent);
    }
}
