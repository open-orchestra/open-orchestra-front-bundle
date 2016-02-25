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
    /**
     * Test the command
     *
     * @param array $siteAlias an array formated as $siteId => $aliasCount
     *
     * @dataProvider provideSiteAlias
     */
    public function testExecute(array $siteAlias)
    {
        $kernel = static::createKernel(array('environment' => 'test' ,'debug' => false));
        $kernel->boot();

        $application = new Application($kernel);
        $application->add(new OrchestraGenerateErrorPagesCommand());

        $command = $application->find('orchestra:errorpages:generate');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()));

        foreach ($siteAlias as $siteId => $aliasCount) {
            $this->assertRegExp(
                '/Generating error pages for siteId ' . $siteId . '/',
                $commandTester->getDisplay()
            );

            for ($i = 0; $i < $aliasCount; $i++) {
                $this->assertRegExp(
                    '/-> ' . $siteId . '\/alias-' . $i . '\/Error 404.html generated/',
                    $commandTester->getDisplay()
                );
                $this->assertFileExists('./web/' . $siteId . '/alias-' . $i . '/Error 404.html');
            }
        }

        $this->assertRegExp('/Done./', $commandTester->getDisplay());
    }

    /**
     * Provide sites aliases
     */
    public function provideSiteAlias()
    {
        return array(
            array(array(2 => 9)),
        );
    }
}
