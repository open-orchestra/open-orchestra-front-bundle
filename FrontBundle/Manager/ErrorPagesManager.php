<?php

namespace OpenOrchestra\FrontBundle\Manager;

use OpenOrchestra\ModelInterface\Model\ReadNodeInterface;
use OpenOrchestra\ModelInterface\Model\NodeInterface;
use OpenOrchestra\ModelInterface\Repository\ReadNodeRepositoryInterface;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Filesystem\Filesystem;
use OpenOrchestra\ModelInterface\Model\ReadSiteInterface;
use OpenOrchestra\BaseBundle\Manager\EncryptionManager;
use Symfony\Component\HttpKernel\Client;

/**
 * Class ErrorPagesManager
 */
class ErrorPagesManager
{
    protected $nodeRepository;
    protected $filesystem;
    protected $kernel;
    protected $router;
    protected $encrypter;

    /**
     * @param ReadNodeRepositoryInterface $nodeRepository
     * @param Filesystem                  $filesystem
     */
    public function __construct(
        ReadNodeRepositoryInterface $nodeRepository,
        Filesystem $filesystem,
        $kernel,
        $router,
        EncryptionManager $encrypter
    ) {
        $this->nodeRepository = $nodeRepository;
        $this->filesystem = $filesystem;
        $this->kernel = $kernel;
        $this->router = $router;
        $this->encrypter = $encrypter;
    }

    /**
     * Generate sitemap for $site
     *
     * @param ReadSiteInterface $site
     *
     * @return string
     */
    public function generatePages(ReadSiteInterface $site)
    {
        $filenames = array();
        $errorNodes = array();
        $client = new Client($this->kernel);

        $nodeCollection = $this->nodeRepository->findAllNodesOfTypeInLastPublishedVersionForSite(NodeInterface::TYPE_ERROR, $site->getSiteId());
        foreach ($nodeCollection as $translatedError) {
            $errorNodes[$translatedError->getLanguage()][$translatedError->getNodeId()] = $translatedError;
        }

        foreach ($site->getAliases() as $aliasId => $alias) {
            foreach ($errorNodes[$alias->getLanguage()] as $errorNode) {
                echo "-----------------------------------------------------------------------------------------------------------\n";

                $url = $this->router->generate(
                    'open_orchestra_base_node_preview',
                    array(
                        'token' => $this->encrypter->encrypt($errorNode->getId()),
                        'nodeId' => $errorNode->getNodeId(),
                        'aliasId' => $aliasId
                    )
                );

                echo "\nurl : $url\n";
                $crawler = $client->request('GET', $url);
                var_dump($crawler->html());
                $filename = $site->getSiteId() . '/alias-' . $aliasId . '/' . $errorNode->getName() . '.html';
                $this->filesystem->dumpFile('web/' . $filename, $crawler->html());
                echo "-----------------------------------------------------------------------------------------------------------\n";
                $filenames[] = $filename;
            }
        }

        return $filenames;
    }
}
