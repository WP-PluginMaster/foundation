<?php

namespace PluginMaster\Foundation\Enqueue;


use PluginMaster\Contracts\Enqueue\EnqueueHandler as EnqueueHandlerContract;

class EnqueueHandler implements EnqueueHandlerContract
{

    /**
     * @var bool
     */
    protected static $admin = false;

    /**
     * @var array
     */
    public $enqueues = [];


    /**
     * @var array
     */
    public $enqueueData = [];

    /**
     * @var null
     */
    protected $appInstance = null;

    /**
     * @var bool
     */
    private $fileLoad = false;


    public function setAppInstance( $app ) {
        $this->appInstance = $app;

        return $this;
    }

    /**
     * @param $enqueueFile
     */
    public function loadEnqueueFile( $enqueueFile ) {
        $this->fileLoad = true;

        require $enqueueFile;

        $this->fileLoad = false;

    }

    public function addAdminEnqueues() {

        foreach ( $this->enqueues['admin']['script'] ?? [] as $adminScript ) {
            wp_enqueue_script( ...$adminScript );
        }

        foreach ( $this->enqueues['admin']['style'] ?? [] as $adminStyle ) {
            wp_enqueue_style( ...$adminStyle );
        }


        foreach ( $this->enqueues['admin']['inline_script'] ?? [] as $inlineScript ) {
            $this->{$inlineScript['type']}( $inlineScript['data'] );
        }

        foreach ( $this->enqueues['admin']['inline_style'] ?? [] as $inlineStyle ) {
            $this->{$inlineStyle['type']}( $inlineStyle['data'] );
        }

    }


    public function addFrontEnqueues() {

        foreach ( $this->enqueues['front']['script'] ?? [] as $adminScript ) {
            wp_enqueue_script( ...$adminScript );
        }

        foreach ( $this->enqueues['front']['style'] ?? [] as $adminStyle ) {
            wp_enqueue_style( ...$adminStyle );
        }

        foreach ( $this->enqueues['front']['inline_script'] ?? [] as $inlineScript ) {
            $this->{$inlineScript['type']}( $inlineScript['data'] );
        }

        foreach ( $this->enqueues['front']['inline_style'] ?? [] as $inlineStyle ) {
            $this->{$inlineStyle['type']}( $inlineStyle['data'] );
        }

    }


    /**
     * @param $data
     * @param bool $admin
     * @param string $type
     */
    public function register( $data, $admin = false, $type = '' ): void {
        $this->enqueueData[] = [ 'data' => $data, 'type' => $type, 'admin' => $admin ];
    }


    public function initEnqueue( $admin = true ) {

        foreach ( $this->enqueueData as $enqueue ) {

            if ( $enqueue['admin'] == $admin ) {

                $script = $enqueue['data'][2];

                if ( $enqueue['type'] ) {

                    $this->enqueues[ $enqueue['admin'] ? 'admin' : 'front' ][ $script ? 'inline_script' : 'inline_style' ][] = [ 'data' => $enqueue['data'], 'type' => $enqueue['type'] ];

                } else {

                    $data  = $this->generateData( $enqueue['data'][0], $enqueue['data'][1], $enqueue['data'][3], $enqueue['data'][4] );
                    $this->enqueues[ $enqueue['admin'] ? 'admin' : 'front' ][ $script ? 'script' : 'style' ][] = $data;

                }
            }

        }

        return $this;
    }


    private function generateData( $path, $options, $header = false, $cdn = false ) {

        $enqueuePath = $cdn ? $path : $this->appInstance->asset( $path );

        $version = $this->appInstance->version();
        $id      = $options['id'] ?? ($options['handle'] ?? 'pluginMaster_' . uniqid());

        $dependency = $options['dependency'] ?? ($options['deps'] ?? []);

        return [ $id, $enqueuePath, $dependency, $version, $header ];
    }


    /**
     * @param $id
     * @param $objectName
     * @param $data
     */
    public function localizeScript( $id, $objectName, $data ) {

        wp_localize_script( $id, $objectName, $data );

    }

    /**
     * @param $data
     * @param $option
     */
    public function inlineScript( $data, $option ) {

        wp_add_inline_script( $options['id'] ?? 'pluginMaster_' . uniqid(), $data, $options['position'] ?? 'after' );

    }

    /**
     * @param $data
     * @param $handle
     */
    public function inlineStyle( $data, $handle ) {

        wp_add_inline_style( $handle ?? 'pluginMaster_' . uniqid(), $data );

    }

}
