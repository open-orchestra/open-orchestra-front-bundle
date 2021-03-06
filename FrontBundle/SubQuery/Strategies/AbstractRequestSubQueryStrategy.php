<?php

namespace OpenOrchestra\FrontBundle\SubQuery\Strategies;

use OpenOrchestra\FrontBundle\SubQuery\SubQueryGeneratorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class AbstractRequestSubQueryStrategy
 */
abstract class AbstractRequestSubQueryStrategy implements SubQueryGeneratorInterface
{
    protected $requestStack;

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }
}
