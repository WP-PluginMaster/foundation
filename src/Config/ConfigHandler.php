<?php

namespace PluginMaster\Foundation\Config;

use PluginMaster\Contracts\Config\ConfigHandlerInterface;

class ConfigHandler implements ConfigHandlerInterface
{
    /**
     * @var string
     */
    protected string $filePath;
    /**
     * @var string
     */
    protected string $fileExtension = '.php';
    /**
     * @var array
     */
    private $data = [];

    /**
     * @param  string  $path
     * @return mixed
     */
    public function setPath(string $path): void
    {
        $this->filePath = $path;
    }

    /**
     * @param  string  $key
     * @return mixed|null
     */
    public function resolveData(string $key)
    {
        $key_tree = explode('.', $key);

        $finalData = [];
        $fileFound = false;
        $filePath = '';

        foreach ($key_tree as $key) {
            $filePath .= DIRECTORY_SEPARATOR.$key;

            if (!$fileFound && is_file($this->addExtension($filePath))) {
                $finalData = $this->setData($filePath);
                $fileFound = true;
            } else {
                if ($fileFound) {
                    $finalData = $finalData[$key] ?? '';
                }
            }
        }

        return $finalData;
    }

    /**
     * @param  string  $file
     * @return string
     */
    protected function addExtension(string $file): string
    {
        return $this->filePath.$file.$this->fileExtension;
    }

    /**
     * @param  string  $filePath
     * @return mixed
     */
    protected function setData(string $filePath)
    {
        if (!isset($this->data[$filePath])) {
            $this->data = include $this->addExtension($filePath);
        }
        return $this->data;
    }

    /**
     * @param  string  $path
     * @return bool
     */
    protected function isDir(string $path): bool
    {
        return is_file($this->filePath.DIRECTORY_SEPARATOR.$path);
    }

    /**
     * @param  string  $path
     * @return bool
     */
    protected function isFile(string $path): bool
    {
        return is_file($this->addExtension($path));
    }
}
