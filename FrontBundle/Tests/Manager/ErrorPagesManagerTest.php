<?php

namespace OpenOrchestra\FrontBundle\Tests\Manager;

use Phake;
use OpenOrchestra\FrontBundle\Manager\ErrorPagesManager;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class ErrorPagesManagerTest
 */
class ErrorPagesManagerTest extends \PHPUnit_Framework_TestCase
{
    protected $nodeRepository;
    protected $filesystem;
    protected $router;
    protected $encrypter;
    protected $errorPagesManager;

    protected $node;
    protected $nodeCollection;
    protected $crawler;
    protected $fakeHtml = 'fake html';
    protected $nodeName = 'nodeName';

    /**
     * Set up the test
     */
    public function setUp()
    {
        $this->nodeRepository = Phake::mock('OpenOrchestra\ModelInterface\Repository\NodeRepositoryInterface');
        $this->node = Phake::mock('OpenOrchestra\ModelInterface\Model\ReadNodeInterface');
        Phake::when($this->node)->getName()->thenReturn($this->nodeName);
        
        $this->nodeCollection = new ArrayCollection();
        $this->nodeCollection->add($this->node);
        Phake::when($this->nodeRepository)->findAllNodesOfTypeInLastPublishedVersionForSite(Phake::anyParameters())->thenReturn($this->nodeCollection);

        $this->filesystem = Phake::mock('Symfony\Component\Filesystem\Filesystem');
        $this->router = Phake::mock('Symfony\Component\Routing\Generator\UrlGeneratorInterface');

        $this->client = Phake::mock('Symfony\Component\HttpKernel\Client');
        $this->crawler = Phake::mock('Symfony\Component\DomCrawler\Crawler');
        Phake::when($this->crawler)->html(Phake::anyParameters())->thenReturn($this->fakeHtml);
        Phake::when($this->client)->request(Phake::anyParameters())->thenReturn($this->crawler);

        $this->encrypter = Phake::mock('OpenOrchestra\BaseBundle\Manager\EncryptionManager');

        $this->errorPagesManager = new ErrorPagesManager($this->nodeRepository, $this->filesystem, $this->client, $this->router, $this->encrypter);
    }

    /**
     * Test generateErrorPages
     */
    public function testGenerateErrorPages()
    {
        $alias1 = Phake::mock('OpenOrchestra\ModelInterface\Model\SiteAliasInterface');
        $alias2 = Phake::mock('OpenOrchestra\ModelInterface\Model\SiteAliasInterface');
        $siteId = 'siteId';
        $site = Phake::mock('OpenOrchestra\ModelInterface\Model\ReadSiteInterface');
        Phake::when($site)->getAliases()->thenReturn(array($alias1, $alias2));
        Phake::when($site)->getSiteId()->thenReturn($siteId);

        $files = $this->errorPagesManager->generateErrorPages($site);

        $expectedFile1 = $siteId . '/alias-0/' . $this->nodeName . '.html';
        $expectedFile2 = $siteId . '/alias-1/' . $this->nodeName . '.html';

        Phake::verify($this->filesystem, Phake::times(1))->dumpFile('web/' . $expectedFile1, $this->fakeHtml);
        Phake::verify($this->filesystem, Phake::times(1))->dumpFile('web/' . $expectedFile2, $this->fakeHtml);
        $this->assertSame(array($expectedFile1, $expectedFile2), $files);
    }
}
