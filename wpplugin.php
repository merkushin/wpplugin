<?php
/*
 * Plugin Name: WP Plugin
 *
 * @version   1.0.0
 * @since     1.0.0
*/

namespace Wpplugin;

require_once __DIR__ . '/vendor/autoload.php';

use Wpplugin\Plugin;

$plugin_file = __FILE__;
$plugin = new Plugin( $plugin_file );
add_action( 'init', [ $plugin, 'init' ] );
