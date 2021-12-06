<?php
declare(strict_types=1);

namespace Psa\Router\Exception;

/**
 * RouteNotFoundException
 */
class RouteNotFoundException extends RouterException
{
    /**
     * The error code.
     *
     * @var integer
     */
    protected $code = 404;
}
