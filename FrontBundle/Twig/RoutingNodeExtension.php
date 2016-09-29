<?php

namespace OpenOrchestra\FrontBundle\Twig;

use OpenOrchestra\BaseBundle\Context\CurrentSiteIdInterface;
use OpenOrchestra\DisplayBundle\Exception\NodeNotFoundException;
use OpenOrchestra\ModelInterface\Model\ReadNodeInterface;
use OpenOrchestra\ModelInterface\Repository\ReadNodeRepositoryInterface;
use Symfony\Bridge\Twig\Extension\RoutingExtension;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class RoutingNodeExtension
 */
class RoutingNodeExtension extends RoutingExtension
{
    protected $siteManager;
    protected $nodeRepository;

    /**
     * @param UrlGeneratorInterface       $generator
     * @param CurrentSiteIdInterface      $siteManager
     * @param ReadNodeRepositoryInterface $nodeRepository
     */
    public function __construct(
        UrlGeneratorInterface $generator,
        CurrentSiteIdInterface $siteManager,
        ReadNodeRepositoryInterface $nodeRepository
    ) {
        parent::__construct($generator);
        $this->siteManager = $siteManager;
        $this->nodeRepository = $nodeRepository;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('url_node', array($this, 'getUrlNode'), array('is_safe_callback' => array($this, 'isUrlGenerationSafe'))),
            new \Twig_SimpleFunction('path_node', array($this, 'getPathNode'), array('is_safe_callback' => array($this, 'isUrlGenerationSafe'))),
        );
    }

    /**
     * @param string $nodeId
     * @param array  $parameters
     * @param bool   $relative
     *
     * @return string
     */
    public function getPathNode($nodeId, $parameters = array(), $relative = false)
    {
        $node = $this->getNode($nodeId);

        return $this->getPath($node->getId(), $parameters, $relative);
    }

    /**
     * @param string $nodeId
     * @param array  $parameters
     * @param bool   $schemeRelative
     *
     * @return string
     */
    public function getUrlNode($nodeId, $parameters = array(), $schemeRelative = false)
    {
        $node = $this->getNode($nodeId);

        return $this->getUrl($node->getId(), $parameters, $schemeRelative);
    }

    /**
     * @param $nodeId
     *
     * @return ReadNodeInterface
     */
    protected function getNode($nodeId)
    {
        $language = $this->siteManager->getCurrentSiteDefaultLanguage();
        $siteId = $this->siteManager->getCurrentSiteId();
        $node = $this->nodeRepository->findOneCurrentlyPublished($nodeId, $language, $siteId);

        if (!$node instanceof ReadNodeInterface) {
            throw new NodeNotFoundException();
        }

        return $node;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'routing_node';
    }
}
