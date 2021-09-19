<?php

namespace PluginMaster\Foundation\SideMenu;

use PluginMaster\Contracts\SideMenu\SideMenuHandler as SideMenuHandlerContract;
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
    public function registerParentMenu( $options, $slug ): void {
        $pageTitle = $options['title'] ?? $options['page_title'];
        $menuTitle = $options['menu_title'] ?? $pageTitle;
        $function  = $this->resolveController( $options['as'] ?? $options['callback'] );
        $icon      = $options['icon'] ?? '';
        $position  = $options['position'] ?? 500;

        $capability = $options['capability'] ?? 'manage_options';

        add_menu_page(
            $pageTitle,
            $menuTitle,
            $capability,
            $slug,
            $function,
            $icon,
            $position
        );

        $this->parentSlug[] = $slug;
    }

    public function resolveController( $callback ) {

        $callbackClass  = null;
        $callbackMethod = null;
        $object         = false;

        if ( is_string( $callback ) ) {

            $segments = explode( $this->methodSeparator, $callback );

            $callbackClass  = $this->controllerNamespace . $segments[0];
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
        $function  = $this->resolveController( $options['as'] ?? $options['callback'] );

        $position   = $options['position'] ?? null;
        $parentSlug = $parentSlug ? $parentSlug : ($options['parent'] ?? $options['parent_slug']);

        $capability = $options['capability'] ?? 'manage_options';

        add_submenu_page(
            $parentSlug,
            $pageTitle,
            $menuTitle,
            $capability,
            $slug,
            $function,
            $position
        );
    }
}
