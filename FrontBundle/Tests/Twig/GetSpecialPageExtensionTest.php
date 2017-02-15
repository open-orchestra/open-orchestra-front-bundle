<?php

namespace OpenOrchestra\FrontBundle\Twig;

use OpenOrchestra\BaseBundle\Tests\AbstractTest\AbstractBaseTestCase;
use OpenOrchestra\ModelInterface\Model\ReadNodeInterface;
use Phake;

/**
 * Class GetSpecialPageExtensionTest
 */
class GetSpecialPageExtensionTest extends AbstractBaseTestCase
{
    /**
     * @var GetSpecialPageExtension
     */
    protected $extension;
    protected $twigEnvironment;
    protected $nodeRepository;
    protected $siteManager;

    /**
     * Set up
     */
    public function setUp()
    {
        $this->twigEnvironment = Phake::mock(\Twig_Environment::class);
        $this->nodeRepository = Phake::mock('OpenOrchestra\ModelInterface\Repository\ReadNodeRepositoryInterface');
        $this->siteManager = Phake::mock('OpenOrchestra\BaseBundle\Context\CurrentSiteIdInterface');
        $language = 'fakeLanguage';
        $siteId = 'fakeSiteId';
        Phake::when($this->siteManager)->getCurrentSiteId()->thenReturn($siteId);
        Phake::when($this->siteManager)->getCurrentSiteDefaultLanguage()->thenReturn($language);

        $this->extension = new GetSpecialPageExtension($this->nodeRepository, $this->siteManager);
    }

    /**
     * Test instance
     */
    public function testInstance()
    {
        $this->assertInstanceOf('Twig_Extension', $this->extension);
    }

    /**
     * Test name
     */
    public function testGetName()
    {
        $this->assertSame('get_special_page', $this->extension->getName());
    }

    /**
     * Test method count
     */
    public function testFunction()
    {
        $this->assertCount(1, $this->extension->getFunctions());
    }

    /**
     * Test get special page
     */
    public function testGetSpecialPage()
    {
        $specialPageName1 = 'specialPageName1';
        $specialPageName2 = 'specialPageName2';
        $specialPage1 = Phake::mock(ReadNodeInterface::class);
        Phake::when($specialPage1)->getSpecialPageName()->thenReturn($specialPageName1);
        $specialPage2 = Phake::mock(ReadNodeInterface::class);
        Phake::when($specialPage2)->getSpecialPageName()->thenReturn($specialPageName2);
        $specialPages = array($specialPage1, $specialPage2);
        Phake::when($this->nodeRepository)->findAllPublishedSpecialPage(Phake::anyParameters())->thenReturn($specialPages);

        $specialPage1Expected = $this->extension->getSpecialPage($this->twigEnvironment, $specialPageName1);
        $specialPage2Expected = $this->extension->getSpecialPage($this->twigEnvironment, $specialPageName2);

        Phake::verify($this->nodeRepository, Phake::times(1))->findAllPublishedSpecialPage(Phake::anyParameters());
        $this->assertSame($specialPage1Expected, $specialPage1);
        $this->assertSame($specialPage2Expected, $specialPage2);
    }
}
