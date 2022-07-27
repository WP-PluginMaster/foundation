<?php

namespace PluginMaster\Foundation\Shortcode;

use PluginMaster\Contracts\Foundation\ApplicationInterface;
use PluginMaster\Contracts\Shortcode\ShortcodeHandlerInterface;
use PluginMaster\Foundation\Resolver\CallbackResolver;

class ShortcodeHandler implements ShortcodeHandlerInterface
{
    /**
     * @var string
     */
    protected string $methodSeparator = '@';


    /**
     * controller namespace
     * @var string
     */
    protected string $controllerNamespace = '';

    /**
     * @var ApplicationInterface
     */
    protected ApplicationInterface $appInstance;


    /**
     * @param  ApplicationInterface  $instance
     * @return $this
     */
    public function setAppInstance(ApplicationInterface $instance): self
    {
        $this->appInstance = $instance;
        return $this;
    }

    /**
     * @param  string  $namespace
     * @return $this
     */
    public function setControllerNamespace(string $namespace): self
    {
        $this->controllerNamespace = $namespace;
        return $this;
    }

    /**
     * @param  string  $shortcodeFile
     * @return void
     */
    public function loadFile(string $shortcodeFile): void
    {
        require $shortcodeFile;
    }

    /**
     * @param  string  $name
     * @param $callback
     */
    public function add(string $name, $callback): void
    {
        $options = [
            "methodSeparator" => $this->methodSeparator,
            'namespace' => $this->controllerNamespace,
            'container' => $this->appInstance
        ];
        add_shortcode($name, CallbackResolver::resolve($callback, $options));
    }

}
