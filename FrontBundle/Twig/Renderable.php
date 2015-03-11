<?php

namespace OpenOrchestra\FrontBundle\Twig;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Templating\TemplateReferenceInterface;

/**
 * trait Renderable
 */
trait Renderable
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @param string|TemplateReferenceInterface $name
     * @param array                             $parameters
     *
     * @return string
     */
    public function render($name, array $parameters = array())
    {
        $device = $this->request->get('X-UA-Device');

        if (!is_null($device) && '' !== $device) {
            $name = $this->getTemplate($name, $device);
        }

        return parent::render($name, $parameters);
    }

    /**
     * @param string $name
     * @param string $device
     *
     * @return string
     */
    public function getTemplate($name, $device)
    {
        if (!is_null($device) && '' !== $device) {
            $templateDevice = $this->replaceTemplateExtension($name, $device);
            if ($this->exists($templateDevice)) {
                return $templateDevice;
            } else {
                if (!empty($this->devices[$device])) {
                    return $this->getTemplate($name, $this->devices[$device]['parent']);
                }
            }
        }

        return $name;
    }

    /**
     * @param string $name
     * @param string $device
     *
     * @return string
     */
    protected function replaceTemplateExtension($name, $device)
    {
        if (strstr($name, 'twig'))
        {
            return str_replace('html.twig', $device . '.html.twig', $name);
        }
        if (strstr($name, 'smarty'))
        {
            return str_replace('html.smarty', $device . '.html.smarty', $name);
        }

        return $name;
    }
}
