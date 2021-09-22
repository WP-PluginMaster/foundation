<?php

namespace PluginMaster\Foundation\Shortcode;

use PluginMaster\Contracts\Shortcode\ShortcodeHandlerInterface ;
use PluginMaster\Foundation\Resolver\CallbackResolver;

class ShortcodeHandler implements ShortcodeHandlerInterface
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
     * @param $shortcodeFile
     * @return void
     */
    public function loadFile( $shortcodeFile ) {
        require $shortcodeFile;
    }

    /**
     * @param $name
     * @param $callback
     */
    public function add( $name, $callback ) {
        $options = [ "methodSeparator" =>  $this->methodSeparator, 'namespace' => $this->controllerNamespace, 'container' => $this->appInstance];
        add_shortcode( $name, CallbackResolver::resolve($callback, $options) );
    }

}
