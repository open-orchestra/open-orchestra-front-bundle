<?php

namespace OpenOrchestra\FrontBundle\Manager;

use OpenOrchestra\DisplayBundle\Exception\NodeNotFoundException;
use OpenOrchestra\DisplayBundle\Manager\ContextInterface;
use OpenOrchestra\ModelInterface\Model\ReadNodeInterface;
use OpenOrchestra\ModelInterface\Repository\ReadNodeRepositoryInterface;
use OpenOrchestra\ModelInterface\Repository\ReadSiteRepositoryInterface;

/**
 * Class NodeManager
 */
class NodeManager
{
    protected $nodeRepository;
    protected $siteRepository;
    protected $currentSiteManager;

    /**
     * @param ReadNodeRepositoryInterface $nodeRepository
     * @param ReadSiteRepositoryInterface $siteRepository
     * @param ContextInterface            $currentSiteManager
     */
    public function __construct(
        ReadNodeRepositoryInterface $nodeRepository,
        ReadSiteRepositoryInterface $siteRepository,
        ContextInterface $currentSiteManager
    ) {
        $this->nodeRepository = $nodeRepository;
        $this->siteRepository = $siteRepository;
        $this->currentSiteManager = $currentSiteManager;
    }

    /**
     * @param string $nodeId
     * @param string $language
     *
     * @return string
     * @throw NodeNotFoundException
     */
    public function getNodeRouteName($nodeId, $language)
    {
        $siteId = $this->currentSiteManager->getCurrentSiteId();

        $node = $this->nodeRepository->findOnePublished($nodeId, $language, $siteId);
        if (!$node instanceof ReadNodeInterface) {
            throw new NodeNotFoundException();
        }

        $site = $this->siteRepository->findOneBySiteId($siteId);

        $aliasId = $site->getAliasIdForLanguage($language);

        return $aliasId . '_' . $node->getId();
    }
}
