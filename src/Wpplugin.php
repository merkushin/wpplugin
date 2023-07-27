<?php declare( strict_types=1 );

namespace Merkushin\Wpplugin;

use Merkushin\Wpal\Service\Assets;
use Merkushin\Wpal\Service\Hooks;
use Merkushin\Wpal\ServiceFactory;

class Wpplugin {
	/**
	 * @var Hooks
	*/
	private $hooks;

	/**
	 * @var Assets
	 */
	private $assets; 

	public function __construct() {
		$this->hooks = ServiceFactory::create_hooks();
		$this->assets = ServiceFactory::create_assets();
	}


	public function init() {
		$this->hooks->add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		$this->hooks->add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	public function enqueue_scripts() {
		/*
		$this->assets->wp_enqueue_script(
			'wpplugin-scripts',
			'wpplugin.js',
			[],
			'1.0.0',
			true
		);
		*/
	}
}
