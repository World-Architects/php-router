<?php
declare(strict_types=1);

namespace Psa\Router;

/**
 * RouteInterface
 */
interface RouteInterface
{
    /**
     * Gets the routes name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Sets the routes name
     *
     * @param string $name Name
     * @return self
     */
    public function setName(string $name): RouteInterface;

    /**
     * Gets the host
     *
     * @return mixed
     */
    public function getHost(): ?HostInterface;

    /**
     * Sets the route host.
     *
     * @param string|null $host The host instance to set or none to get the set one.
     * @param string $scheme HTTP Scheme
     * @return RouteInterface The current host on get or `$this` on set.
     */
    public function setHost(string $host = null, string $scheme = '*'): RouteInterface;

    /**
     * Gets the routes Scope
     *
     * @return \Psa\Router\Scope
     */
    public function getScope(): ?ScopeInterface;

    /**
     * Sets a routes scope
     *
     * @param  \Psa\Router\Scope|null $scope Scope
     * @return $this;
     */
    public function setScope(?Scope $scope): RouteInterface;

    /**
     * Gets the routes handler
     *
     * @return null|callable|\Closure
     */
    public function getHandler(): callable|\Closure|null;

    /**
     * Gets/sets the route's handler.
     *
     * @param mixed $handler The route handler.
     * @return self
     */
    public function setHandler(mixed $handler): RouteInterface;

    /**
     * Checks if the route instance matches a request.
     *
     * @param array $request a request.
     * @param array|null $variables Variables
     * @param array|null $hostVariables Host variables
     * @return bool
     */
    public function match(array $request, array &$variables = null, array &$hostVariables = null): bool;

    /**
      * Returns the route's link.
      *
      * @param  array $params  The route parameters.
      * @param  array $options Options for generating the proper prefix. Accepted values are:
      *                        - `'absolute'` _boolean_: `true` or `false`. - `'scheme'`
      *                        _string_ : The scheme. - `'host'`     _string_ : The host
      *                        name. - `'basePath'` _string_ : The base path. - `'query'`
      *                        _string_ : The query string. - `'fragment'` _string_ : The
      *                        fragment string.
      * @return string          The link.
      */
     public function link(array $params = [], array $options = []): string;
}
