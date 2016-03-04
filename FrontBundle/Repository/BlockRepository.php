<?php

namespace OpenOrchestra\FrontBundle\Repository;

use OpenOrchestra\BaseBundle\Manager\EncryptionManager;
use OpenOrchestra\FrontBundle\Exception\NonExistingBlockException;
use OpenOrchestra\FrontBundle\Exception\NonExistingNodeException;
use OpenOrchestra\ModelInterface\Model\ReadBlockInterface;
use OpenOrchestra\ModelInterface\Model\ReadNodeInterface;
use OpenOrchestra\ModelInterface\Repository\ReadNodeRepositoryInterface;

/**
 * Class BlockRepository
 */
class BlockRepository
{
    protected $nodeRepository;
    protected $encryptionManager;

    /**
     * @param ReadNodeRepositoryInterface $nodeRepository
     */
    public function __construct(ReadNodeRepositoryInterface $nodeRepository, EncryptionManager $encryptionManager)
    {
        $this->nodeRepository = $nodeRepository;
        $this->encryptionManager = $encryptionManager;
    }

    /**
     * @param string      $blockId
     * @param string      $nodeId
     * @param string      $language
     * @param int         $siteId
     * @param string|null $previewToken
     *
     * @return ReadBlockInterface
     * @throws NonExistingBlockException
     * @throws NonExistingNodeException
     */
    public function findBlock($blockId, $nodeId, $language, $siteId, $previewToken = null)
    {
        $node = null;
        if (! is_null($previewToken)) {
            $decryptedId = $this->encryptionManager->decrypt($previewToken);
            $node = $this->nodeRepository->find($decryptedId);
        }

        if ( !($node instanceof ReadNodeInterface) || $nodeId != $node->getNodeId()) {
            /** @var ReadNodeInterface $node */
            $node = $this->nodeRepository->findOneCurrentlyPublished($nodeId, $language, $siteId);
        }

        if (!$node instanceof ReadNodeInterface) {
            throw new NonExistingNodeException();
        }

        if (($block = $node->getBlock($blockId)) instanceof ReadBlockInterface) {
            return $block;
        }

        throw new NonExistingBlockException();
    }
}
