<?php

namespace PluginMaster\Foundation\Action;

use PluginMaster\Contracts\Action\ActionHandlerInterface;
use PluginMaster\Contracts\Foundation\ApplicationInterface;
use PluginMaster\Foundation\Resolver\CallbackResolver;

class ActionHandler implements ActionHandlerInterface
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
     * @var object
     */
    protected object $appInstance;


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
     * @param  string  $actionFile
     * @return void
     */
    public function loadFile(string $actionFile): void
    {
        require $actionFile;
    }

    /**
     * @param  string  $name
     * @param string | callable $callback
     * @param  int  $priority
     */
    public function add(string $name, $callback, int $priority = 10): void
    {
        $options = [
            "methodSeparator" => $this->methodSeparator,
            'namespace' => $this->controllerNamespace,
            'container' => $this->appInstance
        ];

        add_action($name, CallbackResolver::resolve($callback, $options), $priority, 20);
    }

}
