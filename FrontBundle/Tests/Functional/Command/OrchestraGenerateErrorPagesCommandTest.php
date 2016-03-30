<?php

use OpenOrchestra\FrontBundle\Command\OrchestraGenerateErrorPagesCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use OpenOrchestra\BaseBundle\Tests\AbstractTest\AbstractKernelTestCase;

/**
 * Class OrchestraGenerateErrorPagesCommandTest
 */
class OrchestraGenerateErrorPagesCommandTest extends AbstractKernelTestCase
{
    protected $command;

    /**
     * Set Up
     */
    public function setUp()
    {
        static::bootKernel();
        $application = new Application(static::$kernel);
        $application->add(new OrchestraGenerateErrorPagesCommand());
        $this->command = $application->find('orchestra:errorpages:generate');
    }

    /**
     * Test the command
     *
     * @param string $siteId
     *
     * @dataProvider provideSiteAlias
     */
    public function testExecute($siteId)
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(array('command' => $this->command->getName()));
        $this->assertRegExp(
            '/Generating error pages for siteId ' . $siteId . '/',
            $commandTester->getDisplay()
        );

        $site = static::$kernel->getContainer()->get('open_orchestra_model.repository.site')->findOneBySiteId($siteId);
        $aliases = $site->getAliases();

        foreach ($aliases as $key => $alias) {
            $this->assertRegExp(
                '/-> ' . $siteId . '\/alias-' . $key . '\/Error 404.html generated/',
                $commandTester->getDisplay()
            );
            $this->assertFileExists('./web/' . $siteId . '/alias-' . $key . '/Error 404.html');
            $this->assertRegExp(
                '/-> ' . $siteId . '\/alias-' . $key . '\/Error 503.html generated/',
                $commandTester->getDisplay()
            );
            $this->assertFileExists('./web/' . $siteId . '/alias-' . $key . '/Error 503.html');
        }

        $this->assertRegExp('/Done./', $commandTester->getDisplay());
    }

    /**
     * Provide sites aliases
     */
    public function provideSiteAlias()
    {
        return array(
            array('2'),
        );
    }
}
