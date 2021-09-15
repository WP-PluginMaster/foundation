<?php


namespace PluginMaster\Foundation\Api;

use Exception;
use PluginMaster\Contracts\Api\ApiHandler as ApiHandlerContract;

class ApiHandler implements ApiHandlerContract
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
     * callback class name for a route
     *
     * @var
     */
    public $callbackClass;
    /**
     * controller namespace
     * @var string
     */
    protected $controllerNamespace = '';
    /**
     * @var array
     */
    protected $args = [];
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
     * callback method name of the route data processor class
     * @var
     */
    protected $callbackMethod;

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
    public function setAppInstance( $instance ) {
        $this->appInstance = $instance;
        return $this;
    }

    /**
     * set rest api namespace
     * @param $namespace
     * @return $this
     */
    public function setNamespace( $namespace ) {
        $this->restNamespace = $namespace;
        return $this;
    }

    /**
     * set rest api namespace
     * @param $list
     * @return $this
     */
    public function setMiddleware( $list ) {
        $this->middlewareList = $list;
        return $this;
    }

    /**
     * @param $namespace
     * @return $this
     */
    public function setControllerNamespace( $namespace ) {
        $this->controllerNamespace = $namespace;
        return $this;
    }

    /**
     * @param $routes
     */
    public function loadRoutes( $routes ) {
        $this->fileLoad = true;

        require $routes;

        $this->fileLoad = false;
        return $this;
    }


    /**
     * register rest api
     * @param $api
     * @param bool $dynamicRoute
     * @throws Exception
     */
    public function register( $api, $dynamicRoute = false ) {
        $this->restApis[] = [ 'api_data' => $api, 'dynamic' => $dynamicRoute ];

    }

    public function apiGenerate() {

        foreach ( $this->restApis as $api ) {
            $this->dynamicRoute = $api['dynamic'];
            $this->apiProcess( ...$api['api_data'] );
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
     * @throws Exception
     */
    protected function apiProcess( $route, $method, $callback, $public, $prefix, $middleware ) {

        $formattedRoute = $this->formatApiPath( $route );
        $options        = $this->generateApiCallback( $callback, $method );
        if ( !$public ) {
            $options['permission_callback'] = $middleware ? $this->resolveMiddleware( $middleware ) : [ $this, 'check_permission' ];
        }
        return $this->generateWordPressRestAPi( $this->restNamespace, $formattedRoute, $options, $prefix );

    }

    /**
     * format route param for Optional Parameter or Required Parameter
     * @param $route
     * @return string|string[]
     */
    protected function formatApiPath( $route ) {
        if ( strpos( $route, '?}' ) !== false ) {
            $route = $this->optionalParam( $route );
        } else {
            $route = $this->requiredParam( $route );
        }
        return $route;
    }

    /**
     * @param $route
     * @return string|string[]
     */
    protected function optionalParam( $route ) {
        $this->args = [];
        preg_match_all( '#\{(.*?)\}#', $route, $match );
        foreach ( $match[0] as $k => $v ) {
            $route = str_replace( '/' . $v, '(?:/(?P<' . str_replace( '?', '', $match[1][ $k ] ) . '>[-\w]+))?', $route );
            array_push( $this->args, $match[1][ $k ] );
        }
        return $route;
    }

    /**
     * @param $route
     * @return string|string[]
     */
    protected function requiredParam( $route ) {
        $this->args = [];
        preg_match_all( '#\{(.*?)\}#', $route, $match );
        foreach ( $match[0] as $k => $v ) {
            $route = str_replace( $v, '(?P<' . $match[1][ $k ] . '>[-\w]+)', $route );
            array_push( $this->args, $match[1][ $k ] );
        }
        return $route;
    }

    /**
     * @param $callback
     * @param $methods
     * @return array
     */
    protected function generateApiCallback( $callback, $methods ) {

        $object = false;
        if ( is_string( $callback ) ) {

            $segments = explode( $this->methodSeparator, $callback );

            $this->callbackClass  = class_exists( $segments[0] ) ? $segments[0] : $this->controllerNamespace . $segments[0];
            $this->callbackMethod = isset( $segments[1] ) ? $segments[1] : '__invoke';

        }

        if ( is_array( $callback ) ) {

            if ( is_object( $callback[0] ) ) {
                $object              = true;
                $this->callbackClass = $callback[0];
            }

            if ( is_string( $callback[0] ) ) {
                $this->callbackClass = class_exists( $callback[0] ) ? $callback[0] : $this->controllerNamespace . $callback[0];
            }

            $this->callbackMethod = isset( $callback[1] ) ? $callback[1] : '__invoke';

        }


        $instance = $object ? $this->callbackClass : $this->resolveControllerInstance( $this->callbackClass );

        if ( $this->dynamicRoute ) {
            $callback = [ $this, 'resolveDynamicCallback' ];
        } else {
            $callback = [ $instance, $this->callbackMethod ];
        }

        return [
            "methods"  => $methods,
            'callback' => $callback,
            'args'     => $this->args
        ];
    }

    /**
     * @param $class
     * @return mixed
     */
    private function resolveControllerInstance( $class ) {
        return $this->appInstance ? $this->appInstance->get( $class ) : new $class();
    }

    /**
     * @param $middleware
     * @return array|bool
     */
    protected function resolveMiddleware( $middleware ) {

        if ( isset( $this->middlewareList[ $middleware ] ) ) {
            $instance = $this->appInstance ? $this->appInstance->get( $this->middlewareList[ $middleware ] ) : new $this->middlewareList[$middleware]();
            return [ $instance, 'handler' ];
        }

        return false;
    }

    /**
     * @param $restNamespace
     * @param $route
     * @param $options
     * @param null $prefix
     * @return bool
     */
    protected function generateWordPressRestAPi( $restNamespace, $route, $options, $prefix = null ) {
        return register_rest_route(
            $restNamespace,
            $prefix . '/' . $route . ($this->dynamicRoute ? '(?:/(?P<action>[-\w]+))?' : ''),
            $options
        );
    }

    public function resolveDynamicCallback( $request ) {

        $requestMethod = strtolower( $request->get_method() );

        $methodName = $request['action'] ? $this->makeMethodName( $requestMethod, $request['action'] ) : '__invoke';

        $this->appInstance->get( $this->callbackClass )->{$methodName}( $request );
    }

    private function makeMethodName( $method, $action ) {

        $segments = explode( '-', $action );
        $slug     = '';
        foreach ( $segments as $part ) {
            $slug .= ucfirst( $part );
        }

        return $method . $slug;
    }

    /**
     * @return bool
     */
    public function check_permission() {
        return current_user_can( 'manage_options' );
    }

}
