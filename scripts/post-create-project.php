<?php declare( strict_types=1 );

$projectRoot = dirname( __DIR__ );
$directoryName = basename( $projectRoot );
$slug = slugify( $directoryName );

if ( '' === $slug ) {
	fwrite( STDERR, "Unable to derive a plugin slug from the project directory name.\n" );
	exit( 1 );
}

$namespaceRoot = namespace_root_from_slug( $slug );
$pluginName = plugin_name_from_slug( $slug );
$composerPackageName = $slug . '/' . $slug;
$npmPackageName = $slug;
$bootstrapSource = $projectRoot . '/wpplugin.php';
$bootstrapTarget = $projectRoot . '/' . $slug . '.php';
$renamedBootstrap = false;

if ( file_exists( $bootstrapSource ) && $bootstrapSource !== $bootstrapTarget ) {
	if ( file_exists( $bootstrapTarget ) ) {
		fwrite( STDERR, "Refusing to rename wpplugin.php because {$slug}.php already exists.\n" );
		exit( 1 );
	}

	if ( ! rename( $bootstrapSource, $bootstrapTarget ) ) {
		fwrite( STDERR, "Failed to rename wpplugin.php to {$slug}.php.\n" );
		exit( 1 );
	}

	$renamedBootstrap = true;
}

$replacements = [
	'merkushin/wpplugin' => $composerPackageName,
	'merkushin-wpplugin' => $npmPackageName,
	'WP Plugin' => $pluginName,
	'Merkushin\\Wpplugin' => $namespaceRoot,
	'Wpplugin\\' => $namespaceRoot . '\\',
	'Wpplugin' => $namespaceRoot,
	'wpplugin.php' => $slug . '.php',
	'wpplugin' => $slug,
];

$changedFiles = [];

foreach ( files_to_update( $projectRoot ) as $filePath ) {
	$contents = file_get_contents( $filePath );

	if ( false === $contents ) {
		fwrite( STDERR, "Failed to read {$filePath}.\n" );
		exit( 1 );
	}

	$updatedContents = strtr( $contents, $replacements );

	if ( $updatedContents === $contents ) {
		continue;
	}

	if ( false === file_put_contents( $filePath, $updatedContents ) ) {
		fwrite( STDERR, "Failed to update {$filePath}.\n" );
		exit( 1 );
	}

	$changedFiles[] = relative_path( $filePath, $projectRoot );
}

run_composer_dump_autoload( $projectRoot );

if ( $renamedBootstrap ) {
	$changedFiles[] = $slug . '.php';
}

$changedFiles = array_values( array_unique( $changedFiles ) );
sort( $changedFiles );

echo "Configured plugin template for {$pluginName}.\n";
echo "Slug: {$slug}\n";
echo "Namespace: {$namespaceRoot}\n";

if ( [] === $changedFiles ) {
	echo "No placeholder updates were needed.\n";
	exit( 0 );
}

echo "Updated files:\n";

foreach ( $changedFiles as $changedFile ) {
	echo " - {$changedFile}\n";
}

function files_to_update( string $projectRoot ): array {
	$allowedFileNames = [
		'composer.json',
		'package.json',
		'package-lock.json',
		'README.md',
		'Makefile',
	];
	$allowedExtensions = [
		'php',
		'js',
		'css',
		'json',
		'md',
		'inc',
	];
	$skipDirectories = [
		'.git',
		'build',
		'node_modules',
		'vendor',
	];
	$files = [];
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator(
			$projectRoot,
			FilesystemIterator::SKIP_DOTS
		)
	);

	foreach ( $iterator as $fileInfo ) {
		$filePath = $fileInfo->getPathname();
		$relativePath = relative_path( $filePath, $projectRoot );
		$segments = explode( DIRECTORY_SEPARATOR, $relativePath );

		if ( [] !== array_intersect( $segments, $skipDirectories ) ) {
			continue;
		}

		if ( in_array( $fileInfo->getFilename(), $allowedFileNames, true ) ) {
			$files[] = $filePath;
			continue;
		}

		if ( in_array( $fileInfo->getExtension(), $allowedExtensions, true ) ) {
			$files[] = $filePath;
		}
	}

	sort( $files );

	return $files;
}

function run_composer_dump_autoload( string $projectRoot ): void {
	$command = composer_dump_autoload_command( $projectRoot );
	$descriptors = [
		0 => STDIN,
		1 => STDOUT,
		2 => STDERR,
	];
	$process = proc_open( $command, $descriptors, $pipes, $projectRoot );

	if ( ! is_resource( $process ) ) {
		fwrite( STDERR, "Failed to start Composer to regenerate autoload files.\n" );
		exit( 1 );
	}

	$exitCode = proc_close( $process );

	if ( 0 !== $exitCode ) {
		fwrite( STDERR, "Composer dump-autoload failed with exit code {$exitCode}.\n" );
		exit( $exitCode );
	}
}

function composer_dump_autoload_command( string $projectRoot ): string {
	$composerBinary = getenv( 'COMPOSER_BINARY' );

	if ( is_string( $composerBinary ) && '' !== $composerBinary ) {
		return escapeshellarg( $composerBinary ) . ' dump-autoload --no-scripts';
	}

	$composerPhar = $projectRoot . '/composer.phar';

	if ( file_exists( $composerPhar ) ) {
		return escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( $composerPhar ) . ' dump-autoload --no-scripts';
	}

	return 'composer dump-autoload --no-scripts';
}

function plugin_name_from_slug( string $slug ): string {
	return implode(
		' ',
		array_map(
			static function ( string $segment ): string {
				return ucfirst( $segment );
			},
			explode( '-', $slug )
		)
	);
}

function namespace_root_from_slug( string $slug ): string {
	$namespace = implode(
		'',
		array_map(
			static function ( string $segment ): string {
				return ucfirst( $segment );
			},
			explode( '-', $slug )
		)
	);

	if ( '' === $namespace ) {
		return 'Plugin';
	}

	if ( ! ctype_alpha( $namespace[0] ) ) {
		return 'Plugin' . $namespace;
	}

	return $namespace;
}

function relative_path( string $path, string $root ): string {
	return ltrim( substr( $path, strlen( $root ) ), DIRECTORY_SEPARATOR );
}

function slugify( string $value ): string {
	$slug = strtolower( $value );
	$slug = preg_replace( '/[^a-z0-9]+/', '-', $slug );
	$slug = trim( $slug ?? '', '-' );

	return preg_replace( '/-+/', '-', $slug ) ?? '';
}
