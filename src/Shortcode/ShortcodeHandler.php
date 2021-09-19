<?php

namespace PluginMaster\Foundation\Shortcode;

use WP_Error;
use PluginMaster\Contracts\Shortcode\ShortcodeHandler as ShortcodeHandlerContract;

class ShortcodeHandler implements ShortcodeHandlerContract
{
    /**
     * @var string
     */
    protected $methodSeparator = '@';


    /**
     * controller namespace
     * @var string
     */
    protected $controllerNamespace = '';

    /**
     * @var object
     */
    protected $appInstance;


    /**
     * @param $instance
     * @return $this
     */
    public function setAppInstance( $instance ) {
        $this->appInstance = $instance;
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
     * @param $name
     * @param $callback
     */
    public function add( $name, $callback ) {
        add_shortcode( $name, $this->resolveCallback( $callback ) );
    }


    /**
     * @param $callback
     * @return array
     */
    protected function resolveCallback( $callback ) {

        $object         = false;
        $callbackClass  = null;
        $callbackMethod = null;

        if ( is_string( $callback ) ) {

            $segments = explode( $this->methodSeparator, $callback );

            $callbackClass  = class_exists( $segments[0] ) ? $segments[0] : $this->controllerNamespace . $segments[0];
            $callbackMethod = isset( $segments[1] ) ? $segments[1] : '__invoke';

        }

        if ( is_array( $callback ) ) {

            if ( is_object( $callback[0] ) ) {
                $object        = true;
                $callbackClass = $callback[0];
            }

            if ( is_string( $callback[0] ) ) {
                $callbackClass = class_exists( $callback[0] ) ? $callback[0] : $this->controllerNamespace . $callback[0];
            }

            $callbackMethod = isset( $callback[1] ) ? $callback[1] : '__invoke';

        }


        if ( !$callbackClass || !$callbackMethod ) {
            new WP_Error( 'notfound', "Controller Class or Method not found " );
            exit;
        }

        $instance = $object ? $callbackClass : $this->resolveControllerInstance( $callbackClass );

        return [ $instance, $callbackMethod ];
    }


    /**
     * @param $class
     * @return mixed
     */
    private function resolveControllerInstance( $class ) {
        return $this->appInstance ? $this->appInstance->get( $class ) : new $class();
    }

}
