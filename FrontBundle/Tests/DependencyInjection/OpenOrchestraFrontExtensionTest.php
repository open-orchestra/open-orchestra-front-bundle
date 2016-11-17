<?php

namespace OpenOrchestra\BaseApiBundle\DependencyInjection;

use OpenOrchestra\BaseBundle\Tests\AbstractTest\AbstractBaseTestCase;
use OpenOrchestra\FrontBundle\DependencyInjection\OpenOrchestraFrontExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Class OpenOrchestraFrontExtensionTest
 */
class OpenOrchestraFrontExtensionTest extends AbstractBaseTestCase
{
    /**
     * Test default value configuration
     */
    public function testDefaultConfig()
    {
        $container = $this->loadContainerFromFile('empty');

        $this->assertEquals(array(), $container->getParameter('open_orchestra_front.devices'));
        $this->assertEquals('x-ua-device', $container->getParameter('open_orchestra_front.device_type_field'));
        $defaultTemplateSet = array(
            'default'=>array(
                'templates' => array(
                    'default' => 'OpenOrchestraFrontBundle:Template:default.html.twig'
                )
            ),
        );
        $this->assertEquals($defaultTemplateSet, $container->getParameter('open_orchestra_front.template_set'));
    }

    /**
     * Test configuration with value
     */
    public function testConfigWithValue()
    {
        $container = $this->loadContainerFromFile('value');

        $this->assertEquals(array("fake_name" => array("parent" => "fake_parent")), $container->getParameter('open_orchestra_front.devices'));
        $this->assertEquals('x-ua-fake', $container->getParameter('open_orchestra_front.device_type_field'));
        $defaultTemplateSet = array(
            'fake'=>array(
                'templates' => array(
                    'fake' => 'OpenOrchestraFrontBundle:Template:default.html.twig'
                )
            ),
        );
        $this->assertEquals($defaultTemplateSet, $container->getParameter('open_orchestra_front.template_set'));
    }

    /**
     * @param string $file
     *
     * @return ContainerBuilder
     */
    private function loadContainerFromFile($file)
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.cache_dir', '/tmp');
        $container->registerExtension(new OpenOrchestraFrontExtension());

        $locator = new FileLocator(__DIR__ . '/Fixtures/config/');
        $loader = new YamlFileLoader($container, $locator);
        $loader->load($file . '.yml');
        $container->getCompilerPassConfig()->setOptimizationPasses(array());
        $container->getCompilerPassConfig()->setRemovingPasses(array());
        $container->compile();

        return $container;
    }
}
