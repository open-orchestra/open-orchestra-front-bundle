<?php

namespace OpenOrchestra\FrontBundle\Manager;

use OpenOrchestra\ModelInterface\Model\ReadNodeInterface;
use OpenOrchestra\ModelInterface\Repository\ReadNodeRepositoryInterface;
use OpenOrchestra\ModelInterface\Model\ReadSiteInterface;
use OpenOrchestra\BaseBundle\Manager\EncryptionManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class ErrorPagesManager
 */
class ErrorPagesManager
{
    protected $nodeRepository;
    protected $filesystem;
    protected $client;
    protected $router;
    protected $encrypter;

    /**
     * @param ReadNodeRepositoryInterface $nodeRepository
     * @param Filesystem                  $filesystem
     * @param Client                      $client
     * @param UrlGeneratorInterface       $router
     * @param EncryptionManager           $encrypter
     */
    public function __construct(
        ReadNodeRepositoryInterface $nodeRepository,
        Filesystem $filesystem,
        Client $client,
        UrlGeneratorInterface $router,
        EncryptionManager $encrypter
    ) {
        $this->nodeRepository = $nodeRepository;
        $this->filesystem = $filesystem;
        $this->client = $client;
        $this->router = $router;
        $this->encrypter = $encrypter;
    }

    /**
     * Dump published error pages for $site
     *
     * @param ReadSiteInterface $site
     *
     * @return string
     */
    public function generateErrorPages(ReadSiteInterface $site)
    {
        $filenames = array();
        $errorNodes = array();

        $nodeCollection = $this->nodeRepository->findAllNodesOfTypeInLastPublishedVersionForSite(ReadNodeInterface::TYPE_ERROR, $site->getSiteId());
        foreach ($nodeCollection as $translatedError) {
            $errorNodes[$translatedError->getLanguage()][$translatedError->getNodeId()] = $translatedError;
        }

        foreach ($site->getAliases() as $aliasId => $alias) {
            if (isset($errorNodes[$alias->getLanguage()])) {
                foreach ($errorNodes[$alias->getLanguage()] as $errorNode) {
                    $filenames[] = $this->dumpErrorPageForSiteAlias($errorNode, $aliasId, $site->getSiteId());
                }
            }
        }

        return $filenames;
    }

    /**
     * Dump the single error pages for a siteAlias given by $errorNode
     * 
     * @param ReadNodeInterface $errorNode
     * @param int               $aliasId
     * @param string            $siteId
     */
    protected function dumpErrorPageForSiteAlias(ReadNodeInterface $errorNode, $aliasId, $siteId)
    {
        $url = $this->router->generate(
            'open_orchestra_base_node_preview',
            array(
                'token' => $this->encrypter->encrypt($errorNode->getId()),
                'nodeId' => $errorNode->getNodeId(),
                'aliasId' => $aliasId
            )
        );

        $filepath = $siteId . '/alias-' . $aliasId . '/' . $errorNode->getName() . '.html';
        $crawler = $this->client->request('GET', $url);

        $this->filesystem->dumpFile('web/' . $filepath, $crawler->html());

        return $filepath;
    }
}
