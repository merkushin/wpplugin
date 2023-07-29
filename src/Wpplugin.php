<?php declare( strict_types=1 );

namespace Merkushin\Wpplugin;

use Merkushin\Wpal\Service\Assets;
use Merkushin\Wpal\Service\Hooks;
use Merkushin\Wpal\ServiceFactory;

class Wpplugin {
	/**
	 * Main plugin file path.
	 *
	 * @var string
	 */
	private $plugin_file;

	/**
	 * @var Hooks
	*/
	private $hooks;

	/**
	 * @var Assets
	 */
	private $assets; 

	/**
	 * @var Plugins
	 */
	private $plugins;

	public function __construct( string $plugin_file ) {
		$this->plugin_file = $plugin_file;
		$this->hooks = ServiceFactory::create_hooks();
		$this->assets = ServiceFactory::create_assets();
		$this->plugins = ServiceFactory::create_plugins();
	}


	public function init() {
		$this->hooks->add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_scripts' ] );
		$this->hooks->add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
	}

	public function enqueue_admin_scripts() {
		$this->assets->wp_enqueue_script(
			'wpplugin-admin-scripts',
			$this->plugins->plugin_dir_url( $this->plugin_file ) . '/assets/dist/javascript/admin.js',
			[],
			'1.0.0',
			true
		);
	}

	public function enqueue_frontend_scripts() {
		$this->assets->wp_enqueue_script(
			'wpplugin-frontend-scripts',
			$this->plugins->plugin_dir_url( $this->plugin_file ) . '/assets/dist/javascript/frontend.js',
			[],
			'1.0.0',
			true
		);
	}
}
