<?php

namespace OpenOrchestra\FrontBundle\Twig;

use OpenOrchestra\BaseBundle\Tests\AbstractTest\AbstractBaseTestCase;
use Phake;

/**
 * Class CreateBlockExtensionTest
 */
class CreateBlockExtensionTest extends AbstractBaseTestCase
{
    /**
     * @var CreateBlockExtension
     */
    protected $extension;
    protected $twigEnvironment;
    protected $displayBlockManager;
    protected $siteManager;

    /**
     * Set up
     */
    public function setUp()
    {
        $this->twigEnvironment = Phake::mock(\Twig_Environment::class);
        $blockClass = get_class(Phake::mock('OpenOrchestra\ModelInterface\Model\BlockInterface'));
        $this->displayBlockManager = Phake::mock('OpenOrchestra\DisplayBundle\DisplayBlock\DisplayBlockManager');
        Phake::when($this->displayBlockManager)->show(Phake::anyParameters())->thenReturn(Phake::mock('Symfony\Component\HttpFoundation\Response'));

        $this->siteManager = Phake::mock('OpenOrchestra\BaseBundle\Context\CurrentSiteIdInterface');

        $this->extension = new CreateBlockExtension($blockClass, $this->displayBlockManager, $this->siteManager);
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
        $this->assertSame('create_block', $this->extension->getName());
    }

    /**
     * Test method count
     */
    public function testFunction()
    {
        $this->assertCount(1, $this->extension->getFunctions());
    }

    /**
     * Test create block
     */
    public function testCreateBlock()
    {
        $component = 'fakeComponent';
        $language = 'fakeLanguage';
        $siteId = 'fakeSiteId';

        Phake::when($this->siteManager)->getCurrentSiteId()->thenReturn($siteId);
        Phake::when($this->siteManager)->getCurrentSiteDefaultLanguage()->thenReturn($language);

        $this->extension->createBlock($this->twigEnvironment, $component);

        Phake::verify($this->displayBlockManager)->show(Phake::anyParameters());
    }
}
