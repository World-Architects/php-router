<?php
namespace Lead\Router;

use Closure;

class Route
{
    const FOUND = 0;

    const NOT_FOUND = 404;

    const METHOD_NOT_ALLOWED = 405;

    /**
     * The route's error number.
     *
     * @var integer
     */
    protected $_error = 0;

    /**
     * The route's message.
     *
     * @var string
     */
    protected $_message = 'OK';

    /**
     * Class dependencies.
     *
     * @var array
     */
    protected $_classes = [];

    /**
     * Route's name.
     *
     * @var string
     */
    public $name = '';

    /**
     * Maching scheme.
     *
     * @var string
     */
    public $scheme = '*';

    /**
     * Matching host.
     *
     * @var string
     */
    public $host = '*';

    /**
     * Matching HTTP method.
     *
     * @var string
     */
    public $method = '*';

    /**
     * Named parameter.
     *
     * @var array
     */
    public $params = [];

    /**
     * List of parameters that should persist during dispatching.
     *
     * @var array
     */
    public $persist = [];

    /**
     * Namespace.
     *
     * @var string
     */
    public $namespace = '';

    /**
     * Request.
     *
     * @var mixed
     */
    public $request = null;

    /**
     * Response.
     *
     * @var mixed
     */
    public $response = null;

    /**
     * Dispatched instance.
     *
     * @var object
     */
    public $dispatched = null;

    /**
     * Pattern prefix.
     *
     * @var array
     */
    protected $_prefix = '';

    /**
     * Patterns definition.
     *
     * @var array
     */
    protected $_patterns = [];

    /**
     * Data extracted from route's patterns.
     *
     * @var array
     */
    protected $_data = null;

    /**
     * Rules extracted from route's data.
     *
     * @var array
     */
    protected $_rules = null;

    /**
     * Handler.
     *
     * @var Closure
     */
    protected $_handler = null;

    /**
     * Middlewares.
     *
     * @var array
     */
    protected $_middleware = [];

    /**
     * Constructs a route
     *
     * @param array $config The config array.
     */
    public function __construct($config = []) {
        $defaults = [
            'error'    => static::FOUND,
            'message'  => 'OK',
            'scheme'     => '*',
            'host'       => '*',
            'method'     => '*',
            'prefix'     => '',
            'patterns'   => [],
            'name'       => '',
            'namespace'  => '',
            'handler'    => null,
            'params'     => [],
            'persist'    => [],
            'middleware' => [],
            'classes'    => [
                'parser' => 'Lead\Router\Parser'
            ]
        ];
        $config += $defaults;

        $this->scheme = $config['scheme'];
        $this->host = $config['host'];
        $this->method = $config['method'];
        $this->name = $config['name'];
        $this->namespace = $config['namespace'];
        $this->params = $config['params'];
        $this->persist = $config['persist'];
        $this->handler($config['handler']);

        $this->_classes = $config['classes'];
        $this->_prefix = trim($config['prefix'], '/');
        $this->_prefix = $this->_prefix ? '/' . $this->_prefix . '/' : '/';
        $this->_middleware = (array) $config['middleware'];
        $this->_error = $config['error'];
        $this->_message = $config['message'];

        foreach ((array) $config['patterns'] as $pattern) {
            $this->append($pattern);
        }
    }

    /**
     * Gets the routing error number.
     *
     * @return integer The routing error.
     */
    public function error()
    {
        return $this->_error;
    }

    /**
     * Gets the routing message.
     *
     * @return string The routing message.
     */
    public function message()
    {
        return $this->_message;
    }

    /**
     * Returns route's patterns.
     *
     * @return array The route's patterns.
     */
    public function patterns()
    {
        return $this->_patterns;
    }

    /**
     * Appends a pattern.
     *
     * @param string $pattern
     */
    public function append($pattern)
    {
        $this->_data = null;
        $this->_rules = null;
        $this->_patterns[] = $this->_prefix . ltrim($pattern, '/');
    }

    /**
     * Prepends a pattern.
     *
     * @param string $pattern
     */
    public function prepend($pattern)
    {
        $this->_data = null;
        $this->_rules = null;
        array_unshift($this->_patterns, $this->_prefix . ltrim($pattern, '/'));
    }

    /**
     * Returns the route's data.
     *
     * @return array A collection of routes splited in segments.
     */
    public function data()
    {
        if ($this->_data === null) {
            $parser = $this->_classes['parser'];
            $this->_data = [];
            $this->_rules = null;
            foreach ($this->_patterns as $pattern) {
                $this->_data = array_merge($this->_data, $parser::parse($pattern, '[^/]+'));
            }
        }
        return $this->_data;
    }

    /**
     * Returns the route's rules.
     *
     * @return array A collection of route patterns and their associated variable names.
     */
    public function rules()
    {
        if ($this->_rules === null) {
            $parser = $this->_classes['parser'];
            $this->_rules = $parser::rules($this->data());
        }
        return $this->_rules;
    }

    /**
     * Gets/sets the route's handler.
     *
     * @param  array      $handler The route handler.
     * @return array|self
     */
    public function handler($handler = null)
    {
        if (func_num_args() === 0) {
            return $this->_handler;
        }
        $this->_handler = $handler;
        return $this;
    }

    /**
     * Dispatches the route.
     *
     * @param  mixed $response The outgoing response.
     * @return mixed
     */
    public function dispatch($response = null)
    {
        if ($error = $this->error()) {
            throw new RouterException($this->message(), $error);
        }
        $this->response = $response;
        $request = $this->request;

        $generator = $this->middleware();

        $next = function() use ($request, $response, $generator, &$next) {
            $handler = $generator->current();
            $generator->next();
            return $handler($request, $response, $next);
        };
        return $next();
    }

    /**
     * Generators for middlewares.
     *
     * @return mixed
     */
    public function middleware()
    {
        foreach ($this->_middleware as $middleware) {
            yield $middleware;
        }

        yield function() {
            $handler = $this->handler();
            return $handler($this, $this->response);
        };
    }

    /**
     * Applies a middleware.
     *
     * @param object|Closure A middleware instance of closure.
     */
    public function apply($middleware)
    {
        $this->_middleware[] = $middleware;
        return $this;
    }

    /**
     * Returns the route link.
     *
     * @param  array  $params  The route parameters.
     * @param  array  $options Options for generating the proper prefix. Accepted values are:
     *                         - `'absolute'` _boolean_: `true` or `false`
     *                         - `'scheme'`   _string_ : The scheme
     *                         - `'host'`     _string_ : The host name
     *                         - `'basePath'` _string_ : The base path
     * @return string          The prefixed path, depending on the passed options.
     */
    public function link($params = [], $options = [])
    {
        $defaults = [
            'absolute' => false,
            'scheme'   => 'http',
            'host'     => 'localhost',
            'basePath' => '',
            'query'    => '',
            'fragment' => ''
        ];
        $options += [
            'scheme' => $this->scheme,
            'host'   => $this->host
        ];

        $options = array_filter($options, function($value) { return $value !== '*'; });
        $options += $defaults;

        $params = $params + $this->params;

        $data = $this->data();

        foreach ($data as $segments) {
            $link = '';
            $missing = null;
            foreach ($segments as $segment) {
                if (is_string($segment)) {
                    $link .= $segment;
                    continue;
                }
                if (!array_key_exists($segment[0], $params)) {
                    $missing = $segment[0];
                    break;
                }
                $link .= $params[$segment[0]];
            }
            if (!$missing) {
                break;
            }
        }

        if (!empty($missing)) {
            $patterns = join(',', $this->_patterns);
            throw new RouterException("Missing parameters `'{$segment[0]}'` for route: `'{$this->name}#{$patterns}'`.");
        }
        $basePath = trim($options['basePath'], '/');
        if ($options['basePath']) {
            $basePath = '/' . $basePath;
        }
        $link = isset($link) ? ltrim($link, '/') : '';
        $link = $basePath . ($link ? '/' . $link : $link);
        $query = $options['query'] ? '?' . $options['query'] : '';
        $fragment = $options['fragment'] ? '#' . $options['fragment'] : '';

        if ($options['absolute']) {
            $scheme = $options['scheme'] ? $options['scheme'] . '://' : '//';
            $link = "{$scheme}{$options['host']}{$link}";
        }

        return $link . $query . $fragment;
    }

}
