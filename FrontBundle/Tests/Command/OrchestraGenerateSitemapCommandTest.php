<?php

namespace OpenOrchestra\FrontBundle\Tests\Command;

use Phake;
use OpenOrchestra\FrontBundle\Command\OrchestraGenerateSitemapCommand;
use Symfony\Component\Console\Application;

/**
 * Class OrchestraGenerateSitemapCommandTest
 *
 */
class OrchestraGenerateSitemapCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OrchestraGenerateSitemapCommand
     */
    protected $command;

    protected $container;
    protected $application;

    /**
     * Set up the test
     */
    public function setUp()
    {
        $this->container = Phake::mock('Symfony\Component\DependencyInjection\Container');

        $this->command = new OrchestraGenerateSitemapCommand();
        $this->command->setContainer($this->container);

        $this->application = new Application();
        $this->application->add($this->command);
    }

    /**
     * Test presence and name
     */
    public function testPresenceAndName()
    {
        $command = $this->application->find('orchestra:sitemaps:generate');

        $this->assertInstanceOf('Symfony\Component\Console\Command\Command', $command);
    }

    /**
     * Test the definition
     */
    public function testDefinition()
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('siteId'));
    }
}
