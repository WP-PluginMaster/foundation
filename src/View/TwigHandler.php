<?php


namespace PluginMaster\Foundation\View;


use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TwigHandler
{

    /**
     * @var Environment
     */
    public Environment $twigEnvironment;

    /**
     * @var string
     */
    protected string $textDomain;

    public function __construct(string $viewPath, string $cachePath, string $textDomain, bool $twigAutoReload = false)
    {
        $this->textDomain = $textDomain;
        $loader = new FilesystemLoader($viewPath);

        $this->twigEnvironment = new Environment($loader, [
            'cache' => $cachePath,
            'auto_reload' => $twigAutoReload,
        ]);

        $this->addFunction();
        $this->addFilter();
    }


    /**
     * add custom function for twig
     */
    private function addFunction(): void
    {
        $fn = new TwigFunction('fn', function (...$param) {
            $functionName = $param[0] ?? null;
            if ($functionName) {
                unset($param[0]);
                return $functionName(...$param);
            }
            return null;
        });

        $this->twigEnvironment->addFunction($fn);
    }


    /**
     * add custom filter  for twig template
     */
    private function addFilter(): void
    {
        $translationFilter = new TwigFilter('trans', function ($data) {
            return __($data, $this->textDomain);
        });

        $this->twigEnvironment->addFilter($translationFilter);

        $shortCodeFilter = new TwigFilter('shortcode', function ($data) {
            return do_shortcode("$data");
        });

        $this->twigEnvironment->addFilter($shortCodeFilter);

        $functionFilter = new TwigFilter('fn', function ($data, ...$param) {
            $functionName = $param[0] ?? null;
            if ($functionName && function_exists($param[0])) {
                unset($param[0]);
                return $functionName($data, ...$param);
            }
            return $data;
        });

        $this->twigEnvironment->addFilter($functionFilter);

        $applyFilter = new TwigFilter('apply_filter', function ($string, ...$param) {
            $filterName = $param[0] ?? null;
            if ($filterName) {
                unset($param[0]);
                return apply_filters($filterName, $string, ...$param);
            }

            return $string;
        });

        $this->twigEnvironment->addFilter($applyFilter);

    }

}
