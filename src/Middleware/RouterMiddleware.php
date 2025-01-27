<?php
declare(strict_types=1);

namespace Psa\Router\Middleware;

use Psa\Router\Exception\RouteNotFoundException;
use Psa\Router\RouterInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * RouterMiddleware
 */
class RouterMiddleware implements MiddlewareInterface
{
    /**
     * Router
     *
     * @var \Psa\Router\Router
     */
    protected RouterInterface|\Psa\Router\Router $router;

    /**
     * Not found callback
     *
     * @var callable
     */
    protected $notFoundCallback = null;

    /**
     * Ignore the exception
     *
     * @var bool
     */
    protected bool $ignoreNotFoundException = false;

    /**
     * The request attribute name for the route
     *
     * @var string
     */
    protected string $routeAttribute = 'route';

    /**
     * Constructor
     *
     * @param RouterInterface $router Router
     */
    public function __construct(
        RouterInterface $router
    ) {
        $this->router = $router;
    }

    /**
     * Sets the request attribute name for the route object
     *
     * @param string $name Name
     * @return $this
     */
    public function setRouteAttribute(string $name): self
    {
        $this->routeAttribute = $name;

        return $this;
    }

    /**
     * Sets the flag to ignore the route not found exception
     *
     * @param bool $ignore Ignore
     * @return $this
     */
    public function setIgnoreException(bool $ignore): self
    {
        $this->ignoreNotFoundException = $ignore;

        return $this;
    }

    /**
     * Process an incoming server request and return a response, optionally
     * delegating response creation to a handler.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Request
     * @param \Psr\Http\Server\RequestHandlerInterface $handler Request Handler
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $route = $this->router->route($request);
            $request = $request->withAttribute($this->routeAttribute, $route);
        } catch (RouteNotFoundException $exception) {
            if (is_callable($this->notFoundCallback)) {
                $callable = $this->notFoundCallback;
                $result = $callable($request, $this->router);
                if ($result instanceof ResponseInterface) {
                    return $result;
                }
            }

            if (!$this->ignoreNotFoundException) {
                throw $exception;
            }
        }

        return $handler->handle($request);
    }
}
