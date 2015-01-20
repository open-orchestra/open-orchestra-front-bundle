<?php

namespace PHPOrchestra\FrontBundle\Test\Manager;

use Phake;
use PHPOrchestra\FrontBundle\Manager\SitemapManager;

/**
 * Class SitemapManagerTest
 */
class SitemapManagerTest extends \PHPUnit_Framework_TestCase
{
    protected $nodeRepository;
    protected $router;
    protected $serializer;
    protected $filesystem;
    protected $sitemapManager;

    public function setUp()
    {
        $this->nodeRepository = Phake::mock('PHPOrchestra\ModelBundle\Repository\NodeRepository');
        $this->router = Phake::mock('Symfony\Component\Routing\Generator\UrlGeneratorInterface');
        $this->serializer = Phake::mock('Symfony\Component\Serializer\SerializerInterface');
        $this->filesystem = Phake::mock('Symfony\Component\Filesystem\Filesystem');
        $this->sitemapManager = new SitemapManager(
            $this->nodeRepository,
            $this->router,
            $this->serializer,
            $this->filesystem
        );
    }

    public function testGenerateSitemap()
    {
        $site = Phake::mock('PHPOrchestra\ModelInterface\Model\SiteInterface');

        $this->sitemapManager->generateSitemap($site);
        Phake::verify($this->filesystem, Phake::times(1))->dumpFile(Phake::anyParameters());
    }
}