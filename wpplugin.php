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

$plugin = new Wpplugin();
add_action( 'init', [ $plugin, 'init' ] );
