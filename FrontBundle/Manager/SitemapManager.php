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

    /**
     * @param NodeRepository $nodeRepository
     */
    public function __construct(NodeRepository $nodeRepository)
    {
        $this->nodeRepository = $nodeRepository;
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
                    'loc' => $site->getDomain() . '/' . $this->getPath($node),
                    'lastmod' => $lastmod,
                    'changefreq' => $node->getSitemapChangefreq(),
                    'priority' => $node->getSitemapPriority()
                );
            }
        }

        return $nodes;
    }

    /**
     * Recursive generation of $node Path
     * 
     * @param NodeInterface $node
     * @param string        $path
     */
    protected function getPath(NodeInterface $node, $path = array())
    {
        if (NodeInterface::ROOT_NODE_ID == $node->getNodeId()) {
            return implode('/', array_reverse($path));
        } else {
            $path[] = $node->getAlias();
            $node = $this->nodeRepository
               // ->findOneByNodeIdAndLanguageWithPublishedAndLastVersionAndSiteId($node->getParentId(), $node->getLanguage());
                ->findOneByNodeId($node->getParentId());
            if ($node) {
                return $this->getPath($node, $path);
            } else {
                return '!Error while computing node path!';
            }
        }
    }
}