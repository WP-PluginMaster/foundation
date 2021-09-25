<?php

namespace PluginMaster\Foundation\Config;

use PluginMaster\Contracts\Config\ConfigHandlerInterface ;

class ConfigHandler implements ConfigHandlerInterface
{

    /**
     * @var array
     */
    private $data = [];

    /**
     * @var
     */
    protected $filePath;


    /**
     * @var string
     */
    protected $fileExtension = '.php';


    /**
     * @param $path
     * @return mixed
     */
    public function setPath( $path ) {
        return $this->filePath = $path;
    }

    /**
     * @param $key
     * @return array|mixed|null
     */
    public function resolveData( $key ) {

        $key_tree = explode( '.', $key );

        $finalData = [];
        $fileFound = false;
        $filePath  = '';

        foreach ( $key_tree as $key ) {

            $filePath .= DIRECTORY_SEPARATOR . $key;

            if ( !$fileFound && is_file( $this->addExtension( $filePath ) ) ) {
                $finalData = $this->set_data( $filePath );
                $fileFound = true;
            } else if ( $fileFound ) {
                $finalData = $finalData[ $key ] ?? '';
            }
        }

        return $finalData;
    }

    /**
     * @param $file
     * @return string
     */
    protected function addExtension( $file ) {
        return $this->filePath . $file . $this->fileExtension;
    }

    /**
     * @param $filePath
     * @return mixed
     */
    protected function set_data( $filePath ) {

        if ( !isset( $this->data[ $filePath ] ) ) {

            $this->data = include $this->addExtension( $filePath );
        }

        return $this->data;
    }

    /**
     * @param $path
     * @return bool
     */
    protected function isDir( $path ) {
        return is_file( $this->filePath . DIRECTORY_SEPARATOR . $path );
    }

    /**
     * @param $path
     * @return bool
     */
    protected function isFile( $path ) {
        return is_file( $this->addExtension( $path ) );
    }
}
