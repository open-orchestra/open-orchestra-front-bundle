<?php

namespace OpenOrchestra\FrontBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use OpenOrchestra\ModelInterface\Model\ReadSiteInterface;

class OrchestraGenerateErrorPagesCommand extends ContainerAwareCommand
{
    /**
     * Configure command
     */
    protected function configure()
    {
        $this
            ->setName('orchestra:errorpages:generate')
            ->setDescription('Generate all error pages')
            ->addOption('siteId', null, InputOption::VALUE_REQUIRED, 'If set, will generate error pages only for this site');
    }

    /**
     * Execute command
     * 
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int 0 if everything went fine, or an error code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $context = $this->getContainer()->get('router')->getContext();
        $context->setBaseUrl('');

        if ($siteId = $input->getOption('siteId')) {
            $site = $this->getContainer()->get('open_orchestra_model.repository.site')->findOneBySiteId($siteId);
            if ($site) {
                $this->generateErrorPages($site, $output);
            } else {
                $output->writeln("<error>No website found with siteId " . $siteId . ".</error>");

                return 1;
            }
        } else {
            $siteCollection = $this->getContainer()->get('open_orchestra_model.repository.site')->findByDeleted(false);
            if ($siteCollection) {
                foreach ($siteCollection as $site) {
                    $this->generateErrorPages($site, $output);
                }
            }
        }

        $output->writeln("\n<info>Done.</info>");

        return 0;
    }

    /**
     * Call sitemap generation for $site
     * 
     * @param ReadSiteInterface $site
     * @param OutputInterface $output
     */
    protected function generateErrorPages(ReadSiteInterface $site, OutputInterface $output)
    {
        $errorPagesManager = $this->getContainer()->get('open_orchestra_front.manager.error_pages');

        $output->writeln("\n<info>Generating error pages for siteId " . $site->getSiteId() . "</info>");

        $filenames = $errorPagesManager->generatePages($site);
        foreach ($filenames as $filename) {
            $output->writeln("<comment>-> " . $filename . " generated</comment>");
        }
    }
}
