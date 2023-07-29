<?php
/*
 * Plugin Name: WP Plugin
 *
 * @version   1.0.0
 * @since     1.0.0
*/

namespace Merkushin\Wpplugin;

require_once __DIR__ . '/vendor/autoload.php';

use Merkushin\Wpplugin\Wpplugin;

$plugin_file = __FILE__;
$plugin = new Wpplugin( $plugin_file );
add_action( 'init', [ $plugin, 'init' ] );
