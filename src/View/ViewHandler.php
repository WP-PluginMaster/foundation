<?php

namespace PluginMaster\Foundation\View;

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class ViewHandler
{

    /**
     * @var object
     */
    protected $twig;

    /**
     * @var string
     */
    protected $viewPath;


    /**
     * @var string
     */
    protected $cachePath;


    /**
     * @var string
     */
    protected $textDomain;

    /**
     * @var string
     */
    protected $twigAutoReload;

    /**
     * @param $viewPath
     * @param  array  $twigOptions
     * @return $this
     */
    public function setConfig($viewPath, $twigOptions = [])
    {

        $this->viewPath       = $viewPath;
        $this->cachePath      = $twigOptions['cache_path'] ?? null;
        $this->textDomain     = $twigOptions['text_domain'] ?? null;
        $this->twigAutoReload = $twigOptions['auto_reload'] ?? false;

        return $this;
    }

    /**
     * @param $path
     * @param  array  $data
     * @param  bool  $noTemplate
     * @return bool
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function render($path, $data = [], $noTemplate = false)
    {

        $viewPath = '';

        foreach (explode('.', $path) as $path) {
            $viewPath .= '/'.$path;
        }

        $path = $viewPath;

        if (!$noTemplate && $this->cachePath) {
            return $this->resolveTwig($path, $data);
        } else {
            return $this->resolvePHP($path, $data);
        }
    }

    /**
     * @param $path
     * @param $data
     * @return bool
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    protected function resolveTwig($path, $data = [])
    {

        if (!$this->twig) {

            $twig       = new TwigHandler($this->viewPath, $this->cachePath, $this->textDomain, $this->twigAutoReload);
            $this->twig = $twig->twigEnvironment;
        }

        echo $this->twig->render($path.'.php', $data);
        return true;
    }


    /**
     * @param $path
     * @param $data
     * @return bool
     */
    protected function resolvePHP($path, $data = [])
    {

        if (count($data)) {
            extract($data);
        }

        include $this->viewPath.$path.'.php';
        return true;
    }

}
