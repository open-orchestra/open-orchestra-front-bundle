<?php

namespace PHPOrchestra\FrontBundle\Manager;

use Symfony\Component\Filesystem\Filesystem;
use PHPOrchestra\ModelInterface\Model\SiteInterface;

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
     * @param SiteInterface   $site
     */
    public function generateRobots(SiteInterface $site)
    {
        $txtContent = $site->getRobotsTxt();
        $filename = 'robots.' . $site->getDomain() . '.txt';
        $this->filesystem->dumpFile('web/' . $filename, $txtContent);

        return $filename;
    }
}
