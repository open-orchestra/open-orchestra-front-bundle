<?php

namespace OpenOrchestra\FrontBundle\Manager;

use OpenOrchestra\ModelInterface\Repository\NodeRepositoryInterface;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use OpenOrchestra\ModelInterface\Model\SiteInterface;
use OpenOrchestra\ModelInterface\Model\NodeInterface;

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
     * @param NodeRepositoryInterface $nodeRepository
     * @param UrlGeneratorInterface   $router
     * @param SerializerInterface     $serializer
     * @param Filesystem              $filesystem
     */
    public function __construct(
        NodeRepositoryInterface $nodeRepository,
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
     * @param SiteInterface $site
     *
     * @return string
     */
    public function generateSitemap(SiteInterface $site)
    {
        $nodes = $this->getSitemapNodesFromSite($site);
        $filename = $site->getSiteId() . '/sitemap.xml';

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
        $nodesCollection = $this->nodeRepository->findLastPublishedVersionByLanguageAndSiteId($site->getMainAlias()->getLanguage(), $site->getSiteId());

        if ($nodesCollection) {
            foreach($nodesCollection as $node) {
                if (is_null($node->getRole())) {
                    $sitemapChangefreq = $node->getSitemapChangefreq();
                    if (is_null($sitemapChangefreq)) {
                        $sitemapChangefreq = $site->getSitemapChangefreq();
                    }

                    $sitemapPriority = $node->getSitemapPriority();
                    if (is_null($sitemapPriority)) {
                        $sitemapPriority = $site->getSitemapPriority();
                    }

                    if ($lastmod = $node->getUpdatedAt()) {
                        $lastmod = $lastmod->format('Y-m-d');
                    }

                    $mainAlias = $site->getMainAlias();
                    $alias = ('' != $mainAlias->getPrefix()) ? $mainAlias->getDomain() . "/" . $mainAlias->getPrefix() : $mainAlias->getDomain();

                    try {
                        $url = $this->router->generate($node->getId());
                        $nodes[] = array(
                            'loc' => $alias . $url,
                            'lastmod' => $lastmod,
                            'changefreq' => $sitemapChangefreq,
                            'priority' => $sitemapPriority
                        );
                    } catch (MissingMandatoryParametersException $e) {

                    }
                }
            }
        }

        return $nodes;
    }
}
