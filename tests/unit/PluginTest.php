<?php declare(strict_types=1);

namespace Wpplugin\Tests;

use Merkushin\Wpal\ServiceFactory;
use Merkushin\Wpal\Service\Hooks;
use Wpplugin\Plugin;
use PHPUnit\Framework\TestCase; 

class PluginTest extends TestCase {
	public function testInit_Always_AddsActions(): void {
		// Arrange
		$hooks = $this->createMock( Hooks::class );
		ServiceFactory::set_custom_hooks($hooks);

		$plugin = new Plugin( 'wpplugin.php' );

		// Expect
		$hooks
			->expects( $this->exactly( 2 ) )
			->method( 'add_action' )
			->withConsecutive(
				[ 'wp_enqueue_scripts', [ $plugin, 'enqueue_frontend_scripts' ] ],
				[ 'admin_enqueue_scripts', [ $plugin, 'enqueue_admin_scripts' ] ],
			);

		// Act
		$plugin->init();
	}
}
