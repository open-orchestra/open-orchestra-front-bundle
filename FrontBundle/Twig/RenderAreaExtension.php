<?php

namespace OpenOrchestra\FrontBundle\Twig;
use OpenOrchestra\FrontBundle\Exception\NonExistingAreaException;
use OpenOrchestra\ModelInterface\Model\ReadAreaInterface;
use OpenOrchestra\ModelInterface\Model\ReadNodeInterface;


/**
 * Class RenderAreaExtension
 */
class RenderAreaExtension extends \Twig_Extension
{
    /**
     * @return array
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction(
                'render_area',
                array($this, 'renderArea'),
                array('needs_environment' => true, 'is_safe' => array('html'))
            ),
        );
    }

    /**
     * @return string
     */
    /**
     * @param \Twig_Environment $env
     * @param string            $name
     * @param ReadNodeInterface $node
     * @param array             $parameters
     *
     * @return string
     * @throws NonExistingAreaException
     */
    public function renderArea(\Twig_Environment $env, $name, ReadNodeInterface $node, array $parameters = array())
    {
        $area = $node->getArea($name);
        if (!$area instanceof ReadAreaInterface) {
            throw new NonExistingAreaException();
        }

        $parameters = array(
            'area'       => $area,
            'parameters' => $parameters,
            'nodeId'     => $node->getNodeId(),
            'siteId'     => $node->getSiteId(),
            '_locale'    => $node->getLanguage()
        );

        return $env->render("OpenOrchestraFrontBundle:Node:area.html.twig", $parameters);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'render_area';
    }
}
