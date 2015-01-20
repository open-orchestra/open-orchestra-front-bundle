<?php

namespace PHPOrchestra\FrontBundle\Test\Manager;

use Phake;
use PHPOrchestra\FrontBundle\Manager\RobotsManager;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class RobotsManagerTest
 */
class RobotsManagerTest extends \PHPUnit_Framework_TestCase
{
    protected $filesystem;
    protected $robotsManager;

    protected $txtContent = 'txt';
    protected $siteDomain = 'domain';

    /**
     * Set up the test
     */
    public function setUp()
    {
        $this->filesystem = Phake::mock('Symfony\Component\Filesystem\Filesystem');

        $this->robotsManager = new RobotsManager($this->filesystem);
    }

    /**
     * Test generateRobots
     */
    public function testGenerateRobots()
    {
        $site = Phake::mock('PHPOrchestra\ModelInterface\Model\SiteInterface');
        Phake::when($site)->getDomain()->thenReturn($this->siteDomain);
        Phake::when($site)->getRobotsTxt()->thenReturn($this->txtContent);

        $this->robotsManager->generateRobots($site);

        Phake::verify($this->filesystem, Phake::times(1))->dumpFile('web/robots.' . $this->siteDomain . '.txt', $this->txtContent);
    }
}
