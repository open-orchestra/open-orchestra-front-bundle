<?php

namespace OpenOrchestra\FrontBundle\Manager;

use OpenOrchestra\FrontBundle\Exception\NonExistingTemplateException;

/**
 * Class TemplateManager
 */
class TemplateManager
{
    protected $templateSet = array();

    /**
     * @param string $name
     * @param string $templateSetName
     *
     * @return string
     * @throws NonExistingTemplateException
     */
    public function getTemplate($name, $templateSetName)
    {
        if (
            isset($this->templateSet[$templateSetName]) &&
            isset($this->templateSet[$templateSetName]['templates']) &&
            isset($this->templateSet[$templateSetName]['templates'][$name])
        ) {
            return $this->templateSet[$templateSetName]['templates'][$name];
        }

        throw new NonExistingTemplateException();
    }

    /**
     * @param array $templateSet
     */
    public function setTemplateSet(array $templateSet)
    {
        $this->templateSet = $templateSet;
    }
}
