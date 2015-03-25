<?php

namespace OpenOrchestra\FrontBundle\Manager;

use Symfony\Component\Filesystem\Filesystem;
use OpenOrchestra\ModelInterface\Model\ReadSiteInterface;

/**
 * Class RobotsManager
 */
class RobotsManager
{
    protected $filesystem;

    /**
     * @param Filesystem  $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * Generate robots.txt for $site
     * 
     * @param ReadSiteInterface $site
     *
     * @return string
     */
    public function generateRobots(ReadSiteInterface $site)
    {
        $txtContent = $site->getRobotsTxt();
        $filename = $site->getSiteId() . '/robots.txt';
        $this->filesystem->dumpFile('web/' . $filename, $txtContent);

        return $filename;
    }
}
