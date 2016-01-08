<?php

namespace OpenOrchestra\FrontBundle\Tests\Manager;

use OpenOrchestra\BaseBundle\Tests\AbstractTest\AbstractBaseTestCase;
use Phake;
use OpenOrchestra\FrontBundle\Manager\RobotsManager;

/**
 * Class RobotsManagerTest
 */
class RobotsManagerTest extends AbstractBaseTestCase
{
    protected $filesystem;
    protected $robotsManager;

    protected $txtContent = 'txt';
    protected $siteId = 'siteId';

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
        $site = Phake::mock('OpenOrchestra\ModelInterface\Model\ReadSiteInterface');
        Phake::when($site)->getSiteId()->thenReturn($this->siteId);
        Phake::when($site)->getRobotsTxt()->thenReturn($this->txtContent);

        $this->robotsManager->generateRobots($site);

        Phake::verify($this->filesystem, Phake::times(1))->dumpFile('web/' . $this->siteId . '/robots.txt', $this->txtContent);
    }
}
