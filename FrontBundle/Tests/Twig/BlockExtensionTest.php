<?php

namespace OpenOrchestra\FrontBundle\Twig;

use OpenOrchestra\BaseBundle\Tests\AbstractTest\AbstractBaseTestCase;
use Phake;

/**
 * Class BlockExtensionTest
 */
class BlockExtensionTest extends AbstractBaseTestCase
{
    /**
     * @var BlockExtension
     */
    protected $extension;
    protected $displayBlockManager;
    protected $siteManager;
    protected $blockRepository;

    /**
     * Set up
     */
    public function setUp()
    {
        $blockClass = get_class(Phake::mock('OpenOrchestra\ModelInterface\Model\BlockInterface'));
        $this->displayBlockManager = Phake::mock('OpenOrchestra\DisplayBundle\DisplayBlock\DisplayBlockManager');
        Phake::when($this->displayBlockManager)->show(Phake::anyParameters())->thenReturn(Phake::mock('Symfony\Component\HttpFoundation\Response'));

        $this->siteManager = Phake::mock('OpenOrchestra\BaseBundle\Context\CurrentSiteIdInterface');
        $this->blockRepository = Phake::mock('OpenOrchestra\ModelInterface\Repository\BlockRepositoryInterface');

        $container = Phake::mock('Symfony\Component\DependencyInjection\Container');
        Phake::when($container)->get('open_orchestra_display.manager.site')->thenReturn($this->siteManager);
        Phake::when($container)->get('open_orchestra_display.display_block_manager')->thenReturn($this->displayBlockManager);
        Phake::when($container)->getParameter('open_orchestra_model.document.block.class')->thenReturn($blockClass);
        Phake::when($container)->get('open_orchestra_model.repository.block')->thenReturn($this->blockRepository);

        $this->extension = new BlockExtension();
        $this->extension->setContainer($container);
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
        $this->assertSame('oo_block', $this->extension->getName());
    }

    /**
     * Test method count
     */
    public function testFunction()
    {
        $this->assertCount(2, $this->extension->getFunctions());
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

        $this->extension->createBlock($component);

        Phake::verify($this->displayBlockManager)->show(Phake::anyParameters());
    }

    /**
     * Test render shared block
     */
    public function testRenderSharedBlock()
    {
        $block = Phake::mock('OpenOrchestra\ModelInterface\Model\ReadBlockInterface');
        Phake::when($this->blockRepository)->findOneTransverseBlockByCode(Phake::anyParameters())->thenReturn($block);

        $this->extension->renderSharedBlock('fakeCode', 'fakeLanguage', 'fakeSiteId');

        Phake::verify($this->displayBlockManager)->show(Phake::anyParameters());
    }

    /**
     * Test render shared block without block
     */
    public function testRenderSharedBlockWithoutBlock()
    {
        Phake::when($this->blockRepository)->findOneTransverseBlockByCode(Phake::anyParameters())->thenReturn(null);

        $this->assertEquals('', $this->extension->renderSharedBlock('fakeCode', 'fakeLanguage', 'fakeSiteId'));
    }
}
