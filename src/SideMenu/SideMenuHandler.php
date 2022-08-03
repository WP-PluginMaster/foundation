<?php

namespace PluginMaster\Foundation\SideMenu;

use PluginMaster\Contracts\Foundation\ApplicationInterface;
use PluginMaster\Contracts\SideMenu\SideMenuHandlerInterface;
use PluginMaster\Contracts\SideMenu\SideMenuInterface;
use PluginMaster\Foundation\Resolver\CallbackResolver;

class SideMenuHandler implements SideMenuHandlerInterface
{

    /**
     * @var ApplicationInterface
     */
    public ApplicationInterface $appInstance;

    /**
     * @var bool
     */
    public bool $fileLoad = false;

    /**
     * @var string
     */
    protected string $controllerNamespace = "";

    /**
     * @var string
     */
    protected string $methodSeparator = "@";


    /**
     * @var array
     */
    protected array $parentSlug = [];

    /**
     * @var array
     */
    protected array $subMenus = [];

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
     * @return SideMenuHandler
     */
    public function setNamespace(string $namespace): self
    {
        $this->controllerNamespace = $namespace;
        return $this;
    }

    /**
     * @param  string  $sidemenu
     * @return SideMenuHandler
     */
    public function loadMenuFile(string $sidemenu): self
    {
        require $sidemenu;

        return $this;
    }

    /**
     * @param SideMenuInterface $sideMenuObject
     */
    public function setSideMenu(SideMenuInterface $sideMenuObject): void
    {
        foreach ($sideMenuObject->getData() as $sidemenu){

            $pageTitle = __($sidemenu['title'], $this->appInstance->config('slug'));
            $menuTitle = __($sidemenu['menu_title'], $this->appInstance->config('slug'));
            $callback =  isset($sidemenu['callback']) ? $this->getCallback($sidemenu['callback']): '';

            if(isset($sidemenu['submenu'])) {
                $this->subMenus[] = [
                    $sidemenu['parent_slug'],
                    $pageTitle,
                    $menuTitle,
                    $options['capability'] ?? 'manage_options',
                    $sidemenu['slug'],
                    $callback,
                    $options['position'] ?? 10
                ];

            } else {

                add_menu_page(
                    $pageTitle,
                    $menuTitle,
                    $sidemenu['capability'] ?? 'manage_options',
                    $sidemenu['slug'],
                    $callback,
                    $options['icon'] ?? '',
                    $options['position'] ?? 100
                );

                $this->parentSlug[] = $sidemenu['slug'];
            }
        }

        foreach ($this->subMenus as $subMenu){
            add_submenu_page(...$subMenu);
        }

        $this->removeFirstSubMenu();
    }

    /**
     * @param $callback
     * @return callable|string
     */
    private function getCallback($callback)
    {
        return $callback ? CallbackResolver::resolve($callback, $this->callbackResolverOptions()) : '';
    }

    /**
     * @return array
     */
    private function callbackResolverOptions(): array
    {
        return [
            "methodSeparator" => $this->methodSeparator,
            'namespace' => $this->controllerNamespace,
            'container' => $this->appInstance
        ];
    }

    /**
     *remove first sub-menu
     */
    public function removeFirstSubMenu(): void
    {
        foreach ($this->parentSlug as $slug) {
            remove_submenu_page($slug, $slug);
        }
    }
}
