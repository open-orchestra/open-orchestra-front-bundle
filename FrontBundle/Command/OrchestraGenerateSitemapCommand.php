<?php

namespace PHPOrchestra\FrontBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use PHPOrchestra\ModelInterface\Model\SiteInterface;

class OrchestraGenerateSitemapCommand extends ContainerAwareCommand
{
    /**
     * Configure command
     */
    protected function configure()
    {
        $this
            ->setName('orchestra:sitemaps:generate')
            ->setDescription('Generate all sitemaps')
            ->addOption('siteId', null, InputOption::VALUE_REQUIRED, 'If set, will generate sitemap only for this site');
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
            $site = $this->getContainer()->get('php_orchestra_model.repository.site')->findOneBySiteId($siteId);
            if ($site) {
                $this->generateSitemap($site, $output);
            } else {
                $output->writeln("<error>No website found with siteId " . $siteId . ".</error>");
            }
        } else {
            $siteCollection = $this->getContainer()->get('php_orchestra_model.repository.site')->findByDeleted(false);
            if ($siteCollection) {
                foreach ($siteCollection as $site) {
                    $this->generateSitemap($site, $output);
                }
            }
        }

        $output->writeln("<info>Done.</info>");
    }

    /**
     * Call sitemap generation for $site
     * 
     * @param SiteInterface $site
     * @param OutputInterface $output
     */
    protected function generateSitemap(SiteInterface $site, OutputInterface $output)
    {
        $sitemapManager = $this->getContainer()->get('php_orchestra_front.manager.sitemap');
        $output->writeln("<info>Generating sitemap for siteId " . $site->getSiteId() . " on domain " . $site->getDomain() . "</info>");

        $filename = $sitemapManager->generateSitemap($site);

        $output->writeln("<comment>-> " . $filename . " generated</comment>\n");
    }
}
