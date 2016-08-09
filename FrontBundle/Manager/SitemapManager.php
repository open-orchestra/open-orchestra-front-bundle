<?php

namespace OpenOrchestra\FrontBundle\Manager;

use OpenOrchestra\ModelInterface\Model\ReadNodeInterface;
use OpenOrchestra\ModelInterface\Repository\ReadNodeRepositoryInterface;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use OpenOrchestra\ModelInterface\Model\ReadSiteInterface;

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
     * @param ReadNodeRepositoryInterface $nodeRepository
     * @param UrlGeneratorInterface       $router
     * @param SerializerInterface         $serializer
     * @param Filesystem                  $filesystem
     */
    public function __construct(
        ReadNodeRepositoryInterface $nodeRepository,
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
     * @param ReadSiteInterface $site
     *
     * @return string
     */
    public function generateSitemap(ReadSiteInterface $site)
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
     * @param ReadSiteInterface $site
     *
     * @return array
     */
    protected function getSitemapNodesFromSite(ReadSiteInterface $site)
    {
        $nodes = array();
        $nodesCollection = $this->nodeRepository->findCurrentlyPublishedVersion($site->getMainAlias()->getLanguage(), $site->getSiteId());

        if ($nodesCollection) {
            /** @var ReadNodeInterface $node */
            foreach ($nodesCollection as $node) {
                $nodes[] = $this->generateNodeInfos($node, $site);
            }
        }

        return $nodes;
    }

    /**
     * Generate sitemap informations for $node of $site
     * 
     * @param ReadNodeInterface $node
     * @param ReadSiteInterface $site
     * 
     * @return array
     */
    protected function generateNodeInfos(ReadNodeInterface $node, ReadSiteInterface $site)
    {
        $nodeInfos = array();

        if (is_null($node->getRole())) {
            try {
                $nodeInfos = array(
                    'loc' => $this->router->generate(
                        $site->getMainAliasId() . '_' . $node->getId(),
                        array(),
                        UrlGeneratorInterface::ABSOLUTE_URL
                    ),
                    'lastmod' => $this->getLastModificationDate($node),
                    'changefreq' => $this->getChangeFrequency($node, $site),
                    'priority' => $this->getPriority($node, $site)
                );
            } catch (MissingMandatoryParametersException $e) {

            }
         }

        return $nodeInfos;
    }

    /**
     * Get the changefreq param of $node from $site
     * 
     * @param ReadNodeInterface $node
     * @param ReadSiteInterface $site
     * 
     * @return string
     */
    protected function getChangeFrequency(ReadNodeInterface $node, ReadSiteInterface $site)
    {
        $sitemapChangefreq = $node->getSitemapChangefreq();

        if (is_null($sitemapChangefreq)) {
            $sitemapChangefreq = $site->getSitemapChangefreq();
        }

        return $sitemapChangefreq;
    }

    /**
     * Get the priority param of $node from $site
     * 
     * @param ReadNodeInterface $node
     * @param ReadSiteInterface $site
     * 
     * @return float
     */
    protected function getPriority(ReadNodeInterface $node, ReadSiteInterface $site)
    {
        $sitemapPriority = $node->getSitemapPriority();

        if (is_null($sitemapPriority)) {
            $sitemapPriority = $site->getSitemapPriority();
        }

        return $sitemapPriority;
    }

    /**
     * Get the last modification date of $node
     * 
     * @param ReadNodeInterface $node
     * 
     * @return string
     */
    protected function getLastModificationDate(ReadNodeInterface $node)
    {
        $lastmod = "?";

        if (($date = $node->getUpdatedAt()) instanceof \DateTime) {
            $lastmod = $date->format('Y-m-d');
        }

        return $lastmod;
    }
}
