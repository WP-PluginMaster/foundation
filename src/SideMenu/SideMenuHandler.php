<?php

namespace PluginMaster\Foundation\SideMenu;

use PluginMaster\Contracts\SideMenu\SideMenuHandler as SideMenuHandlerContract;
use PluginMaster\Foundation\Resolver\CallbackResolver;
use WP_Error;

class SideMenuHandler implements SideMenuHandlerContract
{

    /**
     * @var
     */
    public $appInstance;

    /**
     * @var bool
     */
    public $fileLoad = false;

    /**
     * @var string
     */
    protected $controllerNamespace = "";

    /**
     * @var string
     */
    protected $methodSeparator = "@";


    /**
     * @var array
     */
    protected $parentSlug = [];

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
     */
    public function setNamespace( $namespace ) {
        $this->controllerNamespace = $namespace;
        return $this;
    }

    /**
     * @param  $sidemenu
     */
    public function loadMenuFile( $sidemenu ) {
        $this->fileLoad = true;

        require $sidemenu;

        $this->fileLoad = false;
        return $this;
    }


    public function addMenuPage( $slug, $options ) {
        if ( $this->fileLoad ) {
            $this->registerParentMenu( $options, $slug );
        } else {
            add_action( 'admin_menu', function () use ( $options, $slug ) {
                $this->registerParentMenu( $options, $slug );
            } );
        }
    }

    /**
     * @param $options
     * @param $slug
     */
    public function registerParentMenu( $options, $slug ) {

        $pageTitle = $options['title'] ?? $options['page_title'];
        $menuTitle = $options['menu_title'] ?? $pageTitle;

        add_menu_page(
            $pageTitle,
            $menuTitle,
            $options['capability'] ?? 'manage_options',
            $slug,
            CallbackResolver::resolve( $options['as'] ?? $options['callback'], $this->callbackResolverOptions() ),
            $options['icon'] ?? '',
            $options['position'] ?? 100
        );

        $this->parentSlug[] = $slug;
    }

    private function callbackResolverOptions() {
        return [ "methodSeparator" => $this->methodSeparator, 'namespace' => $this->controllerNamespace, 'container' => $this->appInstance ] ;
    }

    /**
     *remove first sub-menu
     */
    public function removeFirstSubMenu() {

        foreach ( $this->parentSlug as $slug ) {
            remove_submenu_page( $slug, $slug );
        }

    }

    public function validateOptions( $options, $parent = true ) {
        $requiredOption = [];

        if ( !isset( $options['title'] ) || !isset( $options['page_title'] ) ) {
            $requiredOption[] = 'title/page_title';
        }

        if ( !isset( $options['as'] ) || !isset( $options['callback'] ) ) {
            $requiredOption[] = 'as/callback';
        }

        if ( !$parent && (!isset( $options['parent'] ) || !isset( $options['parent_slug'] )) ) {
            $requiredOption[] = 'parent/parent_slug';
        }

        if ( !empty( $requiredOption ) ) {
            new WP_Error( 'option_missing', "SideNav Option missing. Required Options: " . implode( ', ', $requiredOption ) );
        }
    }

    /**
     * @param $slug
     * @param $options
     * @param null $parentSlug
     * @return mixed
     */
    public function addSubMenuPage( $slug, $options, $parentSlug = null ) {

        $pageTitle = $options['title'] ?? $options['page_title'];
        $menuTitle = $options['menu_title'] ?? $pageTitle;

        add_submenu_page(
            $parentSlug ? $parentSlug : ($options['parent'] ?? $options['parent_slug']),
            $pageTitle,
            $menuTitle,
            $options['capability'] ?? 'manage_options',
            $slug,
            CallbackResolver::resolve( $options['as'] ?? $options['callback'], $this->callbackResolverOptions() ),
            $options['position'] ?? ''
        );
    }
}
