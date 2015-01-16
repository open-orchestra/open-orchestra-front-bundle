<?php

namespace PHPOrchestra\FrontBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use PHPOrchestra\ModelInterface\Model\SiteInterface;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;
use PHPOrchestra\ModelInterface\Model\NodeInterface;

class OrchestraGenerateSitemapCommand extends ContainerAwareCommand
{
    /**
     * Configure command
     */
    protected function configure()
    {
        $this
            ->setName('orchestra:sitemap:generate')
            ->setDescription('Generate sitemaps')
            ->addOption('siteId', null, InputOption::VALUE_REQUIRED, 'If set, will only generate sitemap for this site');
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
            $this->generateSitemap($site, $output);

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
     * Generate sitemap for $site
     * 
     * @param SiteInterface   $site
     * @param OutputInterface $output
     */
    protected function generateSitemap(SiteInterface $site, OutputInterface $output)
    {
        $output->writeln("<info>Generating sitemap for site " . $site->getSiteId() . " on domain " . $site->getDomain() . "</info>");

        $nodes = $this->getSitemapNodesFromSite($site);
        $filename = 'sitemap.' . $site->getDomain() . '.xml';

        $encoders = array(new XmlEncoder('urlset'), new JsonEncoder());
        $normalizers = array(new GetSetMethodNormalizer());
        $serializer = new Serializer($normalizers, $encoders);
        $map['url'] = $nodes;
        
        file_put_contents('web/' . $filename, $serializer->serialize($map, 'xml'));
        $output->writeln("<comment>-> " . $filename . " generated</comment>\n");

        return true;
    }

    /**
     * Return an array of sitemapNodes for $site
     * 
     * @param SiteInterface $site
     * 
     * @return array
     */
    protected function getSitemapNodesFromSite(SiteInterface $site)
    {
        $nodes = array();

        // TODO : récupérer les noeuds en version published uniquement + vision publique
        $nodesCollection = $this->getContainer()->get('php_orchestra_model.repository.node')
            ->findLastVersionBySiteId(NodeInterface::TYPE_DEFAULT, $site->getSiteId());

        if ($nodesCollection) {
            foreach($nodesCollection as $node) {
                if ($lastmod = $node->getUpdatedAt())
                    $lastmod = $lastmod->format('Y-m-d');

                $nodes[] = array(
                    'loc' => $site->getDomain() . '/' . $node->getPath(),
                    'lastmod' => $lastmod,
                    'changefreq' => $node->getSitemapChangefreq(),
                    'priority' => $node->getSitemapPriority()
                );
            }
        }

        return $nodes;
    }
}
