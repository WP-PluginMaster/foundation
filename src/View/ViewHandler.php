<?php


use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;

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
     * @param $viewPath
     * @param array $twigOptions
     * @return $this
     */
    public function setConfig( $viewPath, $twigOptions = [] ) {

        $this->viewPath   = $viewPath;
        $this->cachePath  = $twigOptions['cache_path'] ?? null;
        $this->textDomain = $twigOptions['text_domain'] ?? null;

        return $this;
    }

    /**
     * @param $path
     * @param array $data
     * @return bool
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function render( $path, $data = [] ) {

        if ( $this->cachePath ) {
            return $this->resolveTwig( $path, $data );
        } else {
            return $this->resolvePHP( $path, $data );
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
    protected function resolveTwig( $path, $data ) {

        if ( !$this->twig ) {

            $loader = new FilesystemLoader( $this->viewPath );

            $this->twig = new Environment( $loader, [
                'cache' => $this->cachePath,
            ] );

            $filter = new TwigFilter( 'trans', function ( $string ) {
                return __( $string, $this->textDomain );
            } );

            $this->twig->addFilter( $filter );
        }

        echo $this->twig->render( $path . '.php', $data );
        return true;
    }

    /**
     * @param $path
     * @param $data
     * @return bool
     */
    protected function resolvePHP( $path, $data ) {

        if ( count( $data ) ) {
            extract( $data );
        }

        $viewPath = '';

        foreach ( explode( '.', $path ) as $path ) {
            $viewPath .= '/' . $path;
        }

        include $this->viewPath . $viewPath . '.php';
        return true;
    }


    /**
     * @param null $path
     */
    public function removeCache( $path = null ) {
        $this->recessiveDelete( $this->cachePath . ($path ? DIRECTORY_SEPARATOR . $path : null) );
    }


    /**
     * @param $dirPath
     */
    protected function recessiveDelete( $dirPath ) {
        if ( is_dir( $dirPath ) ) {
            $objects = scandir( $dirPath );
            foreach ( $objects as $file ) {
                if ( !in_array( $file, [ '..', '.' ] ) ) {
                    if ( filetype( $dirPath . "/" . $file ) == "dir" )
                        $this->recessiveDelete( $dirPath . "/" . $file );
                    else unlink( $dirPath . "/" . $file );
                }
            }
            reset( $objects );
            rmdir( $dirPath );
        }
    }

}
