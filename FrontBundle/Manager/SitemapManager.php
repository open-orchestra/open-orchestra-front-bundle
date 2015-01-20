<?php

namespace PHPOrchestra\FrontBundle\Manager;

use PHPOrchestra\ModelBundle\Repository\NodeRepository;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use PHPOrchestra\ModelInterface\Model\SiteInterface;
use PHPOrchestra\ModelInterface\Model\NodeInterface;

/**
 * Class SitemapManager
 */
class SitemapManager
{
    protected $nodeRepository;
    protected $router;
    protected $serializer;
    protected $filesystem;

    /**
     * @param NodeRepository $nodeRepository
     */
    public function __construct(
        NodeRepository $nodeRepository,
        UrlGeneratorInterface $router,
        SerializerInterface $serializer,
        Filesystem $filesystem
    ) {
        $this->nodeRepository = $nodeRepository;
        $this->router = $router;
        $this->serializer = $serializer;
        $this->filesystem = $filesystem;
    }

    /**
     * Generate sitemap for $site
     * 
     * @param SiteInterface   $site
     */
    public function generateSitemap(SiteInterface $site)
    {
        $nodes = $this->getSitemapNodesFromSite($site);
        $filename = 'sitemap.' . $site->getDomain() . '.xml';
        $map['url'] = $nodes;

        $xmlContent = str_replace(
            array('</response>', '<response>'),
            array('</urlset>', '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'),
            $this->serializer->serialize($map, 'xml')
        );
        $this->filesystem->dumpFile('web/' . $filename, $xmlContent);

        return $filename;
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
        $nodesCollection = $this->nodeRepository->findLastVersionBySiteId(NodeInterface::TYPE_DEFAULT, $site->getSiteId());

        if ($nodesCollection) {
            foreach($nodesCollection as $node) {
                if ($lastmod = $node->getUpdatedAt())
                    $lastmod = $lastmod->format('Y-m-d');

                $nodes[] = array(
                    'loc' => $site->getDomain() . $this->router->generate($node->getNodeId()),
                    'lastmod' => $lastmod,
                    'changefreq' => $node->getSitemapChangefreq(),
                    'priority' => $node->getSitemapPriority()
                );
            }
        }

        return $nodes;
    }
}
