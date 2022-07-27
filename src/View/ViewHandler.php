<?php

namespace PluginMaster\Foundation\View;

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class ViewHandler
{
    /**
     * @var string
     */
    protected $viewPath;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var TwigHandler
     */
    protected $twig;

    /**
     * @param $viewPath
     * @param  array  $options
     */
    public function __construct(string $viewPath, array $options = [])
    {
        $this->viewPath = $viewPath;
        $this->options = $options;
    }

    /**
     * @param  string  $viewPath
     * @param  array  $data
     * @param  bool  $noTemplate
     * @return bool
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function render(string $viewPath, array $data = [], bool $noTemplate = false)
    {
        $path = $this->pathResolve($viewPath);
        $templateName = $this->options['template']['name'] ?? 'php';

        if (!$noTemplate && $templateName == 'twig') {
            return $this->resolveTwig($path, $data);
        } else {
            return $this->resolvePHP($path, $data);
        }
    }


    protected function pathResolve($path): string
    {
        $viewPath = '';

        foreach (explode('.', $path) as $path) {
            $viewPath .= '/'.$path;
        }

        return $viewPath;
    }

    /**
     * @param $path
     * @param $data
     * @return bool
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    protected function resolveTwig(string $path, array $data = [])
    {
        if (!$this->twig) {
            $textDomain = $this->options['text_domain'] ?? '';
            $autoReload = $this->options['template']['config']['auto_reload'] ?? false;
            $cachePath = $this->options['cache_path'] ?? '';
            $this->twig = new TwigHandler($this->viewPath, $cachePath, $textDomain, $autoReload);
        }

        echo $this->twig->twigEnvironment->render($path.'.php', $data);
        return true;
    }


    /**
     * @param $path
     * @param $data
     * @return bool
     */
    protected function resolvePHP(string $path, array $data = [])
    {
        if (count($data)) {
            extract($data);
        }

        include $this->viewPath.$path.'.php';
        return true;
    }

}
