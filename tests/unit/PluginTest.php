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

		$expectedCalls = [
			[ 'wp_enqueue_scripts', [ $plugin, 'enqueue_frontend_scripts' ], 10, 1 ],
			[ 'admin_enqueue_scripts', [ $plugin, 'enqueue_admin_scripts' ], 10, 1 ],
		];

		$matcher = $this->exactly(count($expectedCalls));

		// Expect
		$hooks
			->expects($matcher)
			->method( 'add_action' )
			->willReturnCallback( function (...$args) use ( $matcher, $expectedCalls ) {
				$callIndex = $matcher->numberOfInvocations() - 1;
				self::assertSame($expectedCalls[$callIndex], $args);
				return true;
			});

		// Act
		$plugin->init();
	}
}
