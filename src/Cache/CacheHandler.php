<?php

namespace PluginMaster\Foundation\Cache;

use PluginMaster\Contracts\Cache\CacheHandlerInterface;

class CacheHandler implements  CacheHandlerInterface
{

    /**
     * @var object
     */
    protected $appVersion;

    /**
     * @var object
     */
    protected $cachePath;

    /**
     * @param $fileName
     * @param $content
     * @param null $directory
     * @return false|int
     */
    public function createFile( $fileName, $content, $directory = null ) {

        $this->createDir( $directory );

        $fullPath = $this->cacheFilePath( $fileName, $directory );

        return file_put_contents( $fullPath, $content );
    }

    /**
     * @param $path
     * @return bool
     */
    private function createDir( $path ) {
        $fullPath = $this->cachePath( $path );

        if ( !$this->isExist( $fullPath ) ) {
            return mkdir( $fullPath, 0755 , true);
        }

        return true;
    }

    /**
     * @param null $path
     * @return string
     */
    private function cachePath( $path = null ) {
        return $this->cachePath . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }

    public function isExist( $path ) {
        return file_exists( $path );
    }

    /**
     * @param $fileName
     * @param null $directory
     * @return string
     */
    public function cacheFilePath( $fileName, $directory = null ) {
        return $this->cachePath . ($directory ? DIRECTORY_SEPARATOR . $directory : '') . DIRECTORY_SEPARATOR . $this->generateFileName( $fileName );
    }

    /**
     * @param $fileName
     * @return string
     */
    private function generateFileName( $fileName ) {
        return hash( 'md5', $this->appVersion . $fileName );
    }

    /**
     * @param $fileName
     * @param null $directory
     * @return false|int
     */
    public function check( $fileName, $directory = null ) {
        return $this->isExist( $this->cacheFilePath( $fileName, $directory ) );
    }


    /**
     * @param $fileName
     * @param null $directory
     * @return false|int
     */
    public function get( $fileName, $directory = null ) {
        return file_get_contents( $this->cacheFilePath( $fileName, $directory ) );
    }

    /**
     * @return false|int
     */
    public function reset() {
        return rmdir( $this->cachePath );
    }

    /**
     * @param $instance
     * @return mixed
     */
    public function setAppVersion( $instance ) {
        $this->appVersion = $instance;
        return $this;
    }

    /**
     * @param $path
     * @return mixed
     */
    public function setCachePath( $path ) {
        $this->cachePath = $path;
        return $this;
    }

}
