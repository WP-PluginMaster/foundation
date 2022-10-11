<?php


namespace PluginMaster\Foundation\Api;

use Exception;
use PluginMaster\Contracts\Api\ApiHandlerInterface;
use PluginMaster\Contracts\Foundation\ApplicationInterface;
use PluginMaster\Foundation\Resolver\CallbackResolver;
use WP_REST_Request;

class ApiHandler implements ApiHandlerInterface
{

    /**
     * @var bool
     */
    public bool $fileLoad = false;

    /**
     * @var ApplicationInterface
     */
    public ApplicationInterface $appInstance;

    /**
     * controller namespace
     * @var string
     */
    protected string $controllerNamespace = '';


    /**
     * wp rest api namespace
     * @var string
     */
    protected string $restNamespace = '';


    /**
     * @var string
     */
    protected string $methodSeparator = '@';

    /**
     * middleware list
     * @var array
     */
    protected array $middlewareList;


    /**
     * @var object
     */
    protected object $callbackClass;

    /**
     * @var bool
     */
    protected bool $dynamicRoute = false;


    /**
     * @var array
     */
    protected array $restApis = [];

    /**
     * set rest api namespace
     *
     * @param ApplicationInterface $instance
     *
     * @return $this
     */
    public function setAppInstance(ApplicationInterface $instance): self
    {
        $this->appInstance = $instance;

        return $this;
    }

    /**
     * set rest api namespace
     *
     * @param string $namespace
     *
     * @return self
     */
    public function setNamespace(string $namespace): self
    {
        $this->restNamespace = $namespace;

        return $this;
    }

    /**
     * set rest api namespace
     *
     * @param $list
     *
     * @return $this
     */
    public function setMiddleware($list): self
    {
        $this->middlewareList = $list;

        return $this;
    }

    /**
     * @param $namespace
     *
     * @return $this
     */
    public function setControllerNamespace($namespace): self
    {
        $this->controllerNamespace = $namespace;

        return $this;
    }

    /**
     * @param $routes
     */
    public function loadRoutes($routes): self
    {
        $this->fileLoad = true;

        require $routes;

        $this->fileLoad = false;

        return $this;
    }


    /**
     * register rest api
     *
     * @param $api
     * @param bool $dynamicRoute
     *
     * @throws Exception
     */
    public function register($api, bool $dynamicRoute = false): void
    {
        $this->restApis[] = ['api_data' => $api, 'dynamic' => $dynamicRoute];
    }

    public function apiGenerate(): void
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
     *
     * @return bool
     */
    protected function apiProcess($route, $method, $callback, $public, $prefix, $middleware)
    {
        $formattedRoute = $this->formatApiPath($route);

        $options = $this->generateApiCallback($callback, $method);
        if (!$public) {
            $options['permission_callback'] = $middleware ? $this->resolveMiddleware($middleware) : [
                $this,
                'check_permission'
            ];
        } else {
            $options['permission_callback'] = [
                $this,
                'publicApi'
            ];
        }

        $restBase = $prefix . '/' . $formattedRoute . ($this->dynamicRoute ? '(?:/(?P<action>[-\w]+))?' : '');

        return $this->generateWordPressRestAPi($this->restNamespace, $restBase, $options);
    }

    /**
     * format route param for Optional Parameter or Required Parameter
     *
     * @param string $route
     *
     * @return string|string[]
     */
    protected function formatApiPath(string $route)
    {
        if (strpos($route, '}') !== false) {
            if (strpos($route, '?}') !== false) {
                $route = $this->optionalParam($route);
            } else {
                $route = $this->requiredParam($route);
            }
        }

        return $route;
    }

    /**
     * @param string $route
     *
     * @return string
     */
    protected function optionalParam(string $route): string
    {
        preg_match_all('#\{(.*?)\}#', $route, $match);
        foreach ($match[0] as $k => $v) {
            $route = str_replace('/' . $v, '(?:/(?P<' . str_replace('?', '', $match[1][$k]) . '>[-\w]+))?', $route);
        }

        return $route;
    }


    protected function requiredParam(string $route): string
    {
        preg_match_all('#\{(.*?)\}#', $route, $match);
        foreach ($match[0] as $k => $v) {
            $route = str_replace($v, '(?P<' . $match[1][$k] . '>[-\w]+)', $route);
        }

        return $route;
    }

    protected function generateApiCallback($callback, string $methods): array
    {
        $options = [
            "methodSeparator" => $this->methodSeparator,
            'namespace' => $this->controllerNamespace,
            'container' => $this->appInstance
        ];

        $callbackArray = CallbackResolver::resolve($callback, $options);

        if ($this->dynamicRoute) {
            $this->callbackClass = $callbackArray[0];
            $callbackArray = [$this, 'resolveDynamicCallback'];
        }

        return [
            "methods" => $methods,
            'callback' => $callbackArray,
            'args' => []
        ];
    }

    protected function resolveMiddleware(string $middleware)
    {
        if (isset($this->middlewareList[$middleware])) {
            $instance = $this->appInstance ? $this->appInstance->get(
                $this->middlewareList[$middleware]
            ) : new $this->middlewareList[$middleware]();

            return [$instance, 'handler'];
        }

        return false;
    }

    protected function generateWordPressRestAPi(string $restNamespace, string $route, array $options): bool
    {
        return register_rest_route(
            $restNamespace,
            $route,
            $options
        );
    }

    public function resolveDynamicCallback(WP_REST_Request $request)
    {
        $requestMethod = strtolower($request->get_method());

        $methodName = $request['action'] ? $this->makeMethodName($requestMethod, $request['action']) : '__invoke';
        $controllerInstance = is_object($this->callbackClass) ? $this->callbackClass : $this->appInstance->get(
            $this->callbackClass
        );

        return $controllerInstance->{$methodName}($request);
    }

    private function makeMethodName(string $method, string $action): string
    {
        $segments = explode('-', $action);
        $slug = '';
        foreach ($segments as $part) {
            $slug .= ucfirst($part);
        }

        return $method . $slug;
    }

    public function check_permission(): bool
    {
        return current_user_can('manage_options');
    }

    public function publicApi(): bool
    {
        return true;
    }

}
