<?php

namespace PluginMaster\Foundation\Cache;

use PluginMaster\Contracts\Cache\CacheHandlerInterface;

class CacheHandler implements CacheHandlerInterface
{
    /**
     * @var string
     */
    protected string $appVersion;

    /**
     * @var string
     */
    protected string $cachePath;

    /**
     * @param string $fileName
     * @param string $content
     * @param string $directory
     * @return bool
     */
    public function createFile(string $fileName, string $content, string $directory = ''): bool
    {
        $this->createDir($directory);

        $fullPath = $this->cacheFilePath($fileName, $directory);

        return file_put_contents($fullPath, $content);
    }

    /**
     * @param  string  $path
     * @return bool
     */
    private function createDir(string $path): bool
    {
        $fullPath = $this->cachePath($path);

        if (!$this->isExist($fullPath)) {
            return mkdir($fullPath, 0755, true);
        }

        return true;
    }

    /**
     * @param  string|null  $path
     * @return string
     */
    private function cachePath(string $path = null): string
    {
        return $this->cachePath.($path ? DIRECTORY_SEPARATOR.$path : '');
    }

    /**
     * @param  string  $path
     * @return bool
     */
    public function isExist(string $path): bool
    {
        return file_exists($path);
    }

    /**
     * @param  string  $fileName
     * @param  string|null  $directory
     * @return string
     */
    public function cacheFilePath(string $fileName, string $directory = null): string
    {
        return $this->cachePath.($directory ? DIRECTORY_SEPARATOR.$directory : '').DIRECTORY_SEPARATOR.$this->generateFileName(
                $fileName
            );
    }

    /**
     * @param  string  $fileName
     * @return string
     */
    private function generateFileName(string $fileName): string
    {
        return hash('md5', $this->appVersion.$fileName);
    }

    /**
     * @param  string  $fileName
     * @param  string  $directory
     * @return bool
     */
    public function check(string $fileName, string $directory = ''): bool
    {
        return $this->isExist($this->cacheFilePath($fileName, $directory));
    }


    /**
     * @param  string  $fileName
     * @param  string  $directory
     * @return string
     */
    public function get(string $fileName, string $directory = ''): string
    {
        return file_get_contents($this->cacheFilePath($fileName, $directory));
    }

    /**
     * @param  string  $fileName
     * @return bool
     */
    public function reset(string $fileName = ''): bool
    {
        return rmdir($this->cachePath($fileName));
    }

    /**
     * @param  string  $appVersion
     * @return self
     */
    public function setAppVersion(string $appVersion): self
    {
        $this->appVersion = $appVersion;
        return $this;
    }

    /**
     * @param  string  $path
     * @return self
     */
    public function setCachePath(string $path): self
    {
        $this->cachePath = $path;
        return $this;
    }

}
