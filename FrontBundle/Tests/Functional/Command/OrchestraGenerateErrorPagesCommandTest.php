<?php

use OpenOrchestra\FrontBundle\Command\OrchestraGenerateErrorPagesCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use OpenOrchestra\BaseBundle\Tests\AbstractTest\AbstractWebTestCase;

/**
 * Class OrchestraGenerateErrorPagesCommandTest
 */
class OrchestraGenerateErrorPagesCommandTest extends AbstractWebTestCase
{

    protected $application;

    /**
     * Set Up
     */
    public function setUp()
    {
        $client = self::createClient();
        $this->application = new Application($client->getKernel());
        $this->application->setAutoExit(false);
        $this->application->add(new OrchestraGenerateErrorPagesCommand());
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
        $command = $this->application->find('orchestra:errorpages:generate');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()));
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
