<?php

namespace PHPOrchestra\FrontBundle\Manager;

use PHPOrchestra\ModelBundle\Repository\NodeRepository;
use PHPOrchestra\ModelInterface\Model\SiteInterface;
use PHPOrchestra\ModelInterface\Model\NodeInterface;

use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Class SitemapManager
 */
class SitemapManager
{
    protected $nodeRepository;
    protected $router;

    /**
     * @param NodeRepository $nodeRepository
     */
    public function __construct(NodeRepository $nodeRepository, $router)
    {
        $this->nodeRepository = $nodeRepository;
        $this->router = $router;
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

        $encoders = array(new XmlEncoder('urlset'), new JsonEncoder());
        $normalizers = array(new GetSetMethodNormalizer());
        $serializer = new Serializer($normalizers, $encoders);
        $map['url'] = $nodes;
        $xmlContent = $serializer->serialize($map, 'xml');
        $xmlContent = str_replace('<urlset>', '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', $xmlContent);

        file_put_contents('web/' . $filename, $xmlContent);

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