<?php

namespace OpenOrchestra\FrontBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use OpenOrchestra\ModelInterface\Model\ReadSiteInterface;

/**
 * Class OrchestraGenerateRobotsCommand
 *
 */
class OrchestraGenerateRobotsCommand extends ContainerAwareCommand
{
    /**
     * Configure command
     */
    protected function configure()
    {
        $this
            ->setName('orchestra:robots:generate')
            ->setDescription('Generate all robots.txt')
            ->addOption('siteId', null, InputOption::VALUE_REQUIRED, 'If set, will generate robots.txt only for this site');
    }

    /**
     * Execute command
     * 
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($siteId = $input->getOption('siteId')) {
            $site = $this->getContainer()->get('open_orchestra_model.repository.site')->findOneBySiteId($siteId);
            if ($site) {
                $this->generateRobots($site, $output);
            } else {
                $output->writeln("<error>No website found with siteId " . $siteId . ".</error>");
            }
        } else {
            $siteCollection = $this->getContainer()->get('open_orchestra_model.repository.site')->findByDeleted(false);
            if ($siteCollection) {
                foreach ($siteCollection as $site) {
                    $this->generateRobots($site, $output);
                }
            }
        }

        $output->writeln("<info>Done.</info>");
    }

    /**
     * Call robots.txt generation for $site
     * 
     * @param ReadSiteInterface $site
     * @param OutputInterface $output
     */
    protected function generateRobots(ReadSiteInterface $site, OutputInterface $output)
    {
        $robotsManager = $this->getContainer()->get('open_orchestra_front.manager.robots');
        $mainAlias = $site->getMainAlias();
        $alias = ('' != $mainAlias->getPrefix()) ? $mainAlias->getDomain() . "/" . $mainAlias->getPrefix() : $mainAlias->getDomain();
        $output->writeln("<info>Generating robots file for siteId " . $site->getSiteId() . " with alias " . $alias . "</info>");

        $filename = $robotsManager->generateRobots($site);

        $output->writeln("<comment>-> " . $filename . " generated</comment>\n");
    }
}
