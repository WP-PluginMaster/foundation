<?php

namespace PluginMaster\Foundation\Enqueue;


use PluginMaster\Contracts\Enqueue\EnqueueHandlerInterface;

class EnqueueHandler implements EnqueueHandlerInterface {

	/**
	 * @var array
	 */
	public $enqueueData = [];

	/**
	 * @var array
	 */
	public $attributes = [];

	/**
	 * @var null
	 */
	protected $appInstance = null;


	public function setAppInstance( $app ) {

		$this->appInstance = $app;

		return $this;
	}

	/**
	 * @param $enqueueFile
	 */
	public function loadEnqueueFile( $enqueueFile ) {

		require $enqueueFile;

	}

	/**
	 * @param $config
	 */
	public function register( $config ): void {

		$this->enqueueData[] = $config;

	}


	public function initEnqueue() {


		$version = $this->appInstance->version();

		$hookBasedEnqueue = [];

		foreach ( $this->enqueueData as $enqueue ) {


			if ( isset( $enqueue['type'] ) ) {

				$hookBasedEnqueue[ $enqueue['hook'] ]['inline'][] = [
					'fn'    => $enqueue['type'],
					'param' => $enqueue['param']
				];

			} else {

				$path = $this->formatPath( $enqueue['path'], $enqueue['cdn'] ?? false );

				if ( gettype( $enqueue['options'] ) == 'string' ) {

					$id = $enqueue['options'];

					$dependency = [];

				} else {

					$id = ( $enqueue['options']['id'] ?? ( $enqueue['options']['handle'] ?? base64_encode( $path ) ) );

					$dependency = $enqueue['options']['dependency'] ?? ( $enqueue['options']['deps'] ?? [] );

				}


				$script = $enqueue['script'] ?? false;

				if ( $script ) {

					$attribute = $enqueue['options']['attributes'] ?? false;

					if ( $attribute && is_array( $attribute ) ) {
						$this->attributes[ $id ] = $attribute;
					}

					$hookBasedEnqueue[ $enqueue['hook'] ]['script'][] = [
						$id,
						$path,
						$dependency,
						$version,
						$enqueue['in_footer'] ?? false
					];

				} else {

					$hookBasedEnqueue[ $enqueue['hook'] ]['style'][] = [
						$id,
						$path,
						$dependency,
						$version,
						$options['media'] ?? 'all'
					];

				}

			}
		}


		foreach ( $hookBasedEnqueue as $hook => $enqueueData ) {

			add_action( $hook, function () use ( $enqueueData ) {

				foreach ( $enqueueData['inline'] ?? [] as $enqueue ) {
					$this->{$enqueue['fn']}( ...$enqueue['param'] );
				}

				foreach ( $enqueueData['script'] ?? [] as $enqueue ) {
					wp_enqueue_script( ...$enqueue );
				}

				foreach ( $enqueueData['style'] ?? [] as $enqueue ) {
					wp_enqueue_style( ...$enqueue );
				}

			} );

		}

		add_filter( 'script_loader_tag', [ $this, 'processAttributes' ], 59, 3 );

	}

	private function formatPath( $path, $cdn ) {

		return $cdn ? $path : $this->appInstance->asset( $path );
	}

	/**
	 * add custom attribute to script tag
	 *
	 * @param $tag
	 * @param $handle
	 * @param $source
	 *
	 * @return string
	 */
	public function processAttributes( $tag, $handle, $source ) {

		foreach ( $this->attributes as $id => $attributes ) {
			if ( $id === $handle ) {
				$attributes['src'] = $source;
				$attributes['id']  = $id;
				$tag               = sprintf( "<script %s></script>\n", wp_sanitize_script_attributes( $attributes ) );
			}

			return $tag;
		}

		return $tag;
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
		$id = gettype( $option ) == 'string' ? $option : ( $option['id'] ?? ( $option['handle'] ?? 'pluginMaster_' . uniqid() ) );
		wp_add_inline_script( $id, $data, $option['position'] ?? 'after' );

	}

	/**
	 * @param $data
	 * @param $handle
	 */
	public function inlineStyle( $data, $handle ) {

		wp_add_inline_style( $handle ?? 'pluginMaster_' . uniqid(), $data );

	}
}
