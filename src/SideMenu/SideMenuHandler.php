<?php

namespace PluginMaster\Foundation\SideMenu;

use PluginMaster\Contracts\Foundation\ApplicationInterface;
use PluginMaster\Contracts\SideMenu\SideMenuHandlerInterface;
use PluginMaster\Contracts\SideMenu\SideMenuInterface;
use PluginMaster\Foundation\Resolver\CallbackResolver;
use WP_Error;

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
        $this->fileLoad = true;

        require $sidemenu;

        $this->fileLoad = false;
        return $this;
    }

    /**
     * @param SideMenuInterface $sideMenuObject
     */
    public function setSideMenu(SideMenuInterface $sideMenuObject): void
    {
        foreach ($sideMenuObject->getData() as $key => $sidemenu){
            if($key == 0){
                $this->parentSlug[] =  $sidemenu['slug'];
            }

            $pageTitle = __($sidemenu['title'], $this->appInstance->config('slug'));
            $menuTitle = __($sidemenu['menu_title'], $this->appInstance->config('slug'));

            if(isset($sidemenu['submenu'])){

                add_submenu_page(
                    $sidemenu['parent_slug'],
                    $pageTitle,
                    $menuTitle,
                    $sidemenu['capability'] ?? 'manage_options',
                    $sidemenu['slug'],
                    isset($sidemenu['callback']) ? $this->getCallback($sidemenu['callback']) : '',
                    $sidemenu['position'] ?? 10
                );

            }else{

                add_menu_page(
                    $pageTitle,
                    $menuTitle,
                    $sidemenu['capability'] ?? 'manage_options',
                    $sidemenu['slug'],
                    isset($sidemenu['callback']) ? $this->getCallback($sidemenu['callback']) : '',
                    $sidemenu['icon'] ?? '',
                    $sidemenu['position'] ?? 100
                );
            }
        }
    }


    private function getCallback($callback)
    {
        return $callback ? CallbackResolver::resolve($callback, $this->callbackResolverOptions()) : '';
    }

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
