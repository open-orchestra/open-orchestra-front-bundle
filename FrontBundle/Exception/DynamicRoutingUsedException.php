<?php

namespace PHPOrchestra\FrontBundle\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class DynamicRoutingUsedException
 */
class DynamicRoutingUsedException extends HttpException
{
    /**
     * @param null       $message
     * @param \Exception $previous
     * @param array      $headers
     * @param int        $code
     */
    public function __construct($message = null, \Exception $previous = null, array $headers = array(), $code = 0)
    {
        parent::__construct(200, $message, $previous, $headers, $code);
    }
}
