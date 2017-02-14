<?php

namespace OpenOrchestra\FrontBundle\Twig;

use OpenOrchestra\BaseBundle\Context\CurrentSiteIdInterface;
use OpenOrchestra\ModelInterface\Model\ReadNodeInterface;
use OpenOrchestra\ModelInterface\Repository\ReadNodeRepositoryInterface;

/**
 * Class GetSpecialPageExtension
 */
class GetSpecialPageExtension extends \Twig_Extension
{
    protected $nodeRepository;
    protected $siteManager;
    protected $specialPages = array();

    /**
     * @param ReadNodeRepositoryInterface $nodeRepository
     * @param CurrentSiteIdInterface      $siteManager
     */
    public function __construct(
        ReadNodeRepositoryInterface $nodeRepository,
        CurrentSiteIdInterface $siteManager
    ) {
        $this->nodeRepository = $nodeRepository;
        $this->siteManager = $siteManager;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction(
                'get_special_page',
                array($this, 'getSpecialPage'),
                array('needs_environment' => true, 'is_safe' => array('html'))
            ),
        );
    }

    /**
     * @param \Twig_Environment $env
     * @param string            $name
     *
     * @return ReadNodeInterface|null
     */
    public function getSpecialPage(\Twig_Environment $env, $name)
    {
        if (empty($this->specialPages)) {
            $this->loadSpecialPages();
        }

        /** @var ReadNodeInterface $specialPage */
        foreach ($this->specialPages as $specialPage) {
            if ($name === $specialPage->getSpecialPageName()) {
                return $specialPage;
            }
        }

        return null;
    }

    /**
     * Load special pages
     */
    protected function loadSpecialPages()
    {
        $language = $this->siteManager->getCurrentSiteDefaultLanguage();
        $siteId = $this->siteManager->getCurrentSiteId();
        $this->specialPages = $this->nodeRepository->findPublishedSpecialPage($language, $siteId);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'get_special_page';
    }
}
