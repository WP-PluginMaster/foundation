<?php


namespace PluginMaster\Foundation\Api;

use Exception;
use PluginMaster\Contracts\Api\ApiHandlerInterface;
use PluginMaster\Foundation\Resolver\CallbackResolver;
use WP_Error;

class ApiHandler implements ApiHandlerInterface
{

    /**
     * @var bool
     */
    public $fileLoad = false;
    /**
     * @var
     */
    public $appInstance;

    /**
     * controller namespace
     * @var string
     */
    protected $controllerNamespace = '';


    /**
     * wp rest api namespace
     * @var string
     */
    protected $restNamespace = '';


    /**
     * @var string
     */
    protected $methodSeparator = '@';

    /**
     * api list
     * @var
     */
    protected $apis;

    /**
     * api list
     * @var
     */
    protected $middlewareList;


    /**
     * @var
     */
    protected $callbackClass;

    /**
     * @var bool
     */
    protected $dynamicRoute = false;


    /**
     * @var array
     */
    protected $restApis = [];

    /**
     * set rest api namespace
     * @param $instance
     * @return $this
     */
    public function setAppInstance($instance)
    {
        $this->appInstance = $instance;
        return $this;
    }

    /**
     * set rest api namespace
     * @param $namespace
     * @return $this
     */
    public function setNamespace($namespace)
    {
        $this->restNamespace = $namespace;
        return $this;
    }

    /**
     * set rest api namespace
     * @param $list
     * @return $this
     */
    public function setMiddleware($list)
    {
        $this->middlewareList = $list;
        return $this;
    }

    /**
     * @param $namespace
     * @return $this
     */
    public function setControllerNamespace($namespace)
    {
        $this->controllerNamespace = $namespace;
        return $this;
    }

    /**
     * @param $routes
     */
    public function loadRoutes($routes)
    {
        $this->fileLoad = true;

        require $routes;

        $this->fileLoad = false;
        return $this;
    }


    /**
     * register rest api
     * @param $api
     * @param  bool  $dynamicRoute
     * @throws Exception
     */
    public function register($api, $dynamicRoute = false)
    {
        $this->restApis[] = ['api_data' => $api, 'dynamic' => $dynamicRoute];

    }

    public function apiGenerate()
    {

        foreach ($this->restApis as $api) {
            $this->dynamicRoute = $api['dynamic'];
            $this->apiProcess(...$api['api_data']);
        }

    }

    /**
     * @param $route
     * @param $method
     * @param $callback
     * @param $public
     * @param $prefix
     * @param $middleware
     * @return bool
     */
    protected function apiProcess($route, $method, $callback, $public, $prefix, $middleware)
    {

        $formattedRoute = $this->formatApiPath($route);

        $options = $this->generateApiCallback($callback, $method);
        if (!$public) {
            $options['permission_callback'] = $middleware ? $this->resolveMiddleware($middleware) : [
                $this, 'check_permission'
            ];
        }

        $rest_base = $prefix.'/'.$formattedRoute.($this->dynamicRoute ? '(?:/(?P<action>[-\w]+))?' : '');

        return $this->generateWordPressRestAPi($this->restNamespace, $rest_base, $options);

    }

    /**
     * format route param for Optional Parameter or Required Parameter
     * @param $route
     * @return string|string[]
     */
    protected function formatApiPath($route)
    {
        if (strpos($route, '?}') !== false) {
            $route = $this->optionalParam($route);
        } else {
            $route = $this->requiredParam($route);
        }
        return $route;
    }

    /**
     * @param $route
     * @return string|string[]
     */
    protected function optionalParam($route)
    {
        preg_match_all('#\{(.*?)\}#', $route, $match);
        foreach ($match[0] as $k => $v) {
            $route = str_replace('/'.$v, '(?:/(?P<'.str_replace('?', '', $match[1][$k]).'>[-\w]+))?', $route);
        }
        return $route;
    }

    /**
     * @param $route
     * @return string|string[]
     */
    protected function requiredParam($route)
    {
        preg_match_all('#\{(.*?)\}#', $route, $match);
        foreach ($match[0] as $k => $v) {
            $route = str_replace($v, '(?P<'.$match[1][$k].'>[-\w]+)', $route);
        }
        return $route;
    }

    /**
     * @param $callback
     * @param $methods
     * @return array
     */
    protected function generateApiCallback($callback, $methods)
    {

        $options       = [
            "methodSeparator" => $this->methodSeparator, 'namespace' => $this->controllerNamespace,
            'container'       => $this->appInstance
        ];
        $callbackArray = CallbackResolver::resolve($callback, $options);

        if ($this->dynamicRoute) {

            $this->callbackClass = $callbackArray[0];
            $callbackArray       = [$this, 'resolveDynamicCallback'];

        }

        return [
            "methods"  => $methods,
            'callback' => $callbackArray,
            'args'     => []
        ];
    }

    /**
     * @param $middleware
     * @return array|bool
     */
    protected function resolveMiddleware($middleware)
    {

        if (isset($this->middlewareList[$middleware])) {
            $instance = $this->appInstance ? $this->appInstance->get($this->middlewareList[$middleware]) : new $this->middlewareList[$middleware]();
            return [$instance, 'handler'];
        }

        return false;
    }

    /**
     * @param $restNamespace
     * @param $route
     * @param $options
     * @return bool
     */
    protected function generateWordPressRestAPi($restNamespace, $route, $options)
    {
        return register_rest_route(
            $restNamespace,
            $route,
            $options
        );
    }

    public function resolveDynamicCallback($request)
    {

        $requestMethod = strtolower($request->get_method());

        $methodName         = $request['action'] ? $this->makeMethodName($requestMethod,
            $request['action']) : '__invoke';
        $controllerInstance = is_object($this->callbackClass) ? $this->callbackClass : $this->appInstance->get($this->callbackClass);

        return $controllerInstance->{$methodName}($request);
    }

    private function makeMethodName($method, $action)
    {

        $segments = explode('-', $action);
        $slug     = '';
        foreach ($segments as $part) {
            $slug .= ucfirst($part);
        }

        return $method.$slug;
    }

    /**
     * @return bool
     */
    public function check_permission()
    {
        return current_user_can('manage_options');
    }

}
