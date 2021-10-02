<?php


namespace PluginMaster\Foundation\View;


use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TwigHandler
{

    public $twigEnvironment ;
    protected $textDomain ;

    public function __construct( $viewPath, $cachePath, $textDomain) {
        $this->textDomain = $textDomain;
        $loader = new FilesystemLoader( $viewPath );

        $this->twigEnvironment = new Environment( $loader, [
            'cache' => $cachePath,
        ] );

        $this->addFunction();
        $this->addFilter();

    }


    private function addFunction(){


        $fn = new TwigFunction( 'fn', function ( ...$param ) {
            $functionName = $param[0] ?? null;
            if ( $functionName ) {
                unset( $param[0] );

                return $functionName( ...$param );
            }
            return null;
        } );

        $this->twigEnvironment->addFunction( $fn );
    }


    private function addFilter(){

        $translationFilter = new TwigFilter( 'trans', function ( $data ) {
            return __( $data, $this->textDomain );
        } );

        $this->twigEnvironment->addFilter( $translationFilter );

        $shortCodeFilter = new TwigFilter( 'shortcode', function ( $data ) {
            return do_shortcode( "$data" );
        } );

        $this->twigEnvironment->addFilter( $shortCodeFilter );

        $functionFilter = new TwigFilter( 'fn', function ( $data, ...$param ) {
            $functionName = $param[0] ?? null;
            if ( $functionName && function_exists( $param[0] ) ) {
                unset( $param[0] );

                return $functionName( $data, ...$param );

            }

            return $data;
        } );

        $this->twigEnvironment->addFilter( $functionFilter );


        $applyFilter = new TwigFilter( 'apply_filter', function ( $string, ...$param ) {
            $filterName = $param[0] ?? null;
            if ( $filterName ) {
                unset( $param[0] );

                return apply_filters( $filterName, $string, ...$param );

            }

            return $string;
        } );

        $this->twigEnvironment->addFilter( $applyFilter );

    }

}
