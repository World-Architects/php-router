<?php
declare(strict_types=1);

namespace Lead\Router;

/**
 * Scope
 */
class Scope
{
    /**
     * The router instance.
     *
     * @var object
     */
    protected $_router = null;

    /**
     * The parent instance.
     *
     * @var object
     */
    protected $_parent = null;

    /**
     * The scope data.
     *
     * @var array
     */
    protected $_scope = [];

    /**
     * The constructor.
     *
     * @param array $config The config array.
     */
    public function __construct($config = [])
    {
        $defaults = [
            'router' => null,
            'parent' => null,
            'scope'  => []
        ];
        $config += $defaults;

        $this->_router = $config['router'];
        $this->_parent = $config['parent'];
        $this->_scope = $config['scope'] + [
            'name'           => '',
            'scheme'         => '*',
            'host'           => '*',
            'methods'        => '*',
            'prefix'         => '',
            'namespace'      => '',
            'persist'        => []
        ];
    }

    /**
     * Creates a new sub scope based on the instance scope.
     *
     * @param  array $options The route options to scopify.
     * @return $this          The new sub scope.
     */
    public function seed(array $options): self
    {
        return new static(
            [
                'router' => $this->_router,
                'parent' => $this,
                'scope'  => $this->scopify($options)
            ]
        );
    }

    /**
     * Scopes an options array according to the instance scope data.
     *
     * @param  array $options The options to scope.
     * @return array          The scoped options.
     */
    public function scopify(array $options): array
    {
        $scope = $this->_scope;

        if (!empty($options['name'])) {
            $options['name'] = $scope['name'] ? $scope['name'] . '.' . $options['name'] : $options['name'];
        }

        if (!empty($options['prefix'])) {
            $options['prefix'] = $scope['prefix'] . trim($options['prefix'], '/');
            $options['prefix'] = $options['prefix'] ? $options['prefix'] . '/' : '';
        }

        if (isset($options['persist'])) {
            $options['persist'] = ((array) $options['persist']) + $scope['persist'];
        }

        if (isset($options['namespace'])) {
            $options['namespace'] = $scope['namespace'] . trim($options['namespace'], '\\') . '\\';
        }

        return $options + $scope;
    }

    /**
     * Delegates calls to the router instance.
     *
     * @param string $name   The method name.
     * @param array  $params The parameters.
     */
    public function __call($name, $params)
    {
        $this->_router->pushScope($this);
        $result = call_user_func_array([$this->_router, $name], $params);
        $this->_router->popScope();
        return $result;
    }
}
