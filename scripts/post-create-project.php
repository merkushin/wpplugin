<?php declare( strict_types=1 );
/**
 * Turns a fresh copy of the template into your plugin. Composer runs it after
 * `composer create-project merkushin/wpplugin <directory>`.
 *
 * The slug comes from the directory name. In a terminal it asks for the plugin name, author
 * and so on; otherwise it uses the defaults, which environment variables can override:
 * WPPLUGIN_NAME, WPPLUGIN_DESCRIPTION, WPPLUGIN_AUTHOR, WPPLUGIN_AUTHOR_EMAIL,
 * WPPLUGIN_WPORG_USER, WPPLUGIN_URI and WPPLUGIN_NAMESPACE.
 *
 * It then replaces the template's placeholders everywhere, renames wpplugin.php and removes
 * itself and the other template-only files.
 */

$projectRoot = dirname( __DIR__ );
$slug        = slugify( basename( $projectRoot ) );

if ( '' === $slug ) {
	fail( 'Unable to derive a plugin slug from the project directory name.' );
}

// Plugin Check rejects slugs and names with these terms (and others' trademarks).
foreach ( [ 'wordpress', 'plugin', 'woocommerce', 'gutenberg' ] as $restricted ) {
	if ( false !== strpos( $slug, $restricted ) ) {
		fwrite( STDERR, "Warning: WordPress.org doesn't accept plugin slugs containing \"{$restricted}\". Rename the directory and start again.\n" );
	}
}

$interactive = function_exists( 'stream_isatty' ) && stream_isatty( STDIN ) && stream_isatty( STDOUT );
if ( $interactive ) {
	echo "Setting up the plugin \"{$slug}\". Press Enter to accept the [default].\n";
}

$gitName  = git_config( 'user.name' );
$gitEmail = git_config( 'user.email' );

$name        = ask( $interactive, 'WPPLUGIN_NAME', 'Plugin name', words_from_slug( $slug, ' ' ) );
$description = ask( $interactive, 'WPPLUGIN_DESCRIPTION', 'One-line description', 'A WordPress plugin.' );
$author      = ask( $interactive, 'WPPLUGIN_AUTHOR', 'Author', '' !== $gitName ? $gitName : 'Your Name' );
$email       = ask( $interactive, 'WPPLUGIN_AUTHOR_EMAIL', 'Author email', '' !== $gitEmail ? $gitEmail : 'you@example.com' );
$wporgUser   = ask( $interactive, 'WPPLUGIN_WPORG_USER', 'WordPress.org username', slugify( $author ) );
$uri         = ask( $interactive, 'WPPLUGIN_URI', 'Repository or homepage URL (optional)', '' );
$namespace   = ask( $interactive, 'WPPLUGIN_NAMESPACE', 'PHP namespace', namespace_from_slug( $slug ) );

// The values go into PHP comments, JSON and the POT file unescaped.
foreach ( [ $name, $description, $author, $email, $wporgUser, $uri ] as $value ) {
	if ( preg_match( '/["\\\\\n]/', $value ) ) {
		fail( "Quotes, backslashes and line breaks aren't supported: {$value}" );
	}
}

if ( ! preg_match( '/^[A-Za-z_][A-Za-z0-9_]*(\\\\[A-Za-z_][A-Za-z0-9_]*)*$/', $namespace ) ) {
	fail( "\"{$namespace}\" is not a valid PHP namespace." );
}

$prefix = str_replace( '-', '_', $slug );
$vendor = '' !== slugify( $wporgUser ) ? slugify( $wporgUser ) : $slug;

// strtr() tries longer keys first: 'merkushin/wpplugin' before 'wpplugin', and the escaped
// namespace in composer.json ("Wpplugin\\") before 'Wpplugin'.
$replacements = [
	'https://github.com/merkushin/wpplugin' => '' !== $uri ? $uri : 'TODO: link to the public repository',
	'merkushin/wpplugin'                    => $vendor . '/' . $slug,
	'Template for a new WordPress plugin'   => $description,
	'Dmitry Merkushin'                      => $author,
	'merkushin@gmail.com'                   => $email,
	'Contributors: merkushin'               => 'Contributors: ' . $wporgUser,
	'WP Plugin'                             => $name,
	'Wpplugin\\\\'                          => str_replace( '\\', '\\\\', $namespace ) . '\\\\',
	'Wpplugin'                              => $namespace,
	'wpplugin_'                             => $prefix . '_',
	'wpplugin'                              => $slug,
];

// Lines that only make sense with a URL.
$lineRemovals = '' === $uri
	? [
		' * Plugin URI:        https://github.com/merkushin/wpplugin',
		"\t\"homepage\": \"https://github.com/merkushin/wpplugin\",",
		"#. Plugin URI of the plugin\n#: wpplugin.php\nmsgid \"https://github.com/merkushin/wpplugin\"\nmsgstr \"\"\n",
	]
	: [];

remove_template_files( $projectRoot );

$changedFiles = [];
foreach ( files_to_update( $projectRoot ) as $filePath ) {
	$contents = read_file( $filePath );
	$updated  = remove_lines( $contents, $lineRemovals );
	$updated  = strtr( $updated, $replacements );
	if ( 'composer.json' === basename( $filePath ) && dirname( $filePath ) === $projectRoot ) {
		$updated = remove_setup_script_hook( $updated );
	}
	if ( 'readme.txt' === basename( $filePath ) ) {
		$updated = update_tested_up_to( $updated );
	}

	if ( $updated !== $contents ) {
		write_file( $filePath, $updated );
		$changedFiles[] = relative_path( $filePath, $projectRoot );
	}
}

rename_file( $projectRoot . '/languages/wpplugin.pot', $projectRoot . "/languages/{$slug}.pot" );
rename_file( $projectRoot . '/wpplugin.php', $projectRoot . "/{$slug}.php" );

// composer.json changed, so the lock file's hash must be refreshed; then the autoloader
// must pick up the new namespace.
run_composer( $projectRoot, 'update --lock --no-install --no-scripts --no-interaction --quiet' );
run_composer( $projectRoot, 'dump-autoload --no-scripts --quiet' );

echo "\nSet up {$name} ({$slug}, namespace {$namespace}): updated " . count( $changedFiles ) . " files.\n\n";
echo <<<NEXT
Next steps:
  cd {$slug}
  git init && git add -A && git commit -m "Create {$name} from merkushin/wpplugin"
  npm install
  make check      # coding standards, static analysis, tests: all should pass
  make dist       # builds {$slug}.zip

Write your code in src/ and tests in tests/unit/. AGENTS.md explains the layout and
conventions; docs/FIRST-RELEASE.md walks through submitting to WordPress.org.

NEXT;

/**
 * Asks a question in a terminal; otherwise uses the environment variable or the default.
 */
function ask( bool $interactive, string $env, string $question, string $default ): string {
	$fromEnv = getenv( $env );
	if ( is_string( $fromEnv ) && '' !== $fromEnv ) {
		return $fromEnv;
	}
	if ( ! $interactive ) {
		return $default;
	}

	echo '' !== $default ? "{$question} [{$default}]: " : "{$question}: ";
	$answer = fgets( STDIN );
	$answer = false === $answer ? '' : trim( $answer );

	return '' !== $answer ? $answer : $default;
}

function git_config( string $key ): string {
	$value = shell_exec( 'git config --get ' . escapeshellarg( $key ) . ' 2>/dev/null' );

	return is_string( $value ) ? trim( $value ) : '';
}

/**
 * Removes files that belong to the template, not to the plugin made from it.
 */
function remove_template_files( string $projectRoot ): void {
	foreach ( [ '.github/workflows/template.yml', 'scripts/post-create-project.php' ] as $file ) {
		if ( file_exists( $projectRoot . '/' . $file ) && ! unlink( $projectRoot . '/' . $file ) ) {
			fail( "Failed to remove {$file}." );
		}
	}
	if ( is_dir( $projectRoot . '/scripts' ) && [] === array_diff( (array) scandir( $projectRoot . '/scripts' ), [ '.', '..' ] ) ) {
		rmdir( $projectRoot . '/scripts' );
	}

	// The README describes the template; the part between these markers goes.
	$readme   = $projectRoot . '/README.md';
	$contents = read_file( $readme );
	$updated  = preg_replace( '/<!-- template:start -->.*?<!-- template:end -->\n*/s', '', $contents );
	if ( is_string( $updated ) && $updated !== $contents ) {
		write_file( $readme, $updated );
	}
}

function rename_file( string $source, string $target ): void {
	if ( ! file_exists( $source ) || $source === $target ) {
		return;
	}
	if ( file_exists( $target ) ) {
		fail( 'Refusing to rename ' . basename( $source ) . ' because ' . basename( $target ) . ' already exists.' );
	}
	if ( ! rename( $source, $target ) ) {
		fail( 'Failed to rename ' . basename( $source ) . ' to ' . basename( $target ) . '.' );
	}
}

function remove_setup_script_hook( string $composerJson ): string {
	$updated = preg_replace( '/\t\t"post-create-project-cmd": \[\n[^\]]*\],\n/', '', $composerJson );

	return is_string( $updated ) ? $updated : $composerJson;
}

/**
 * Sets "Tested up to" to the current WordPress version, so a new plugin doesn't start out of date.
 */
function update_tested_up_to( string $readme ): string {
	$context  = stream_context_create( [ 'http' => [ 'timeout' => 5 ] ] );
	$response = @file_get_contents( 'https://api.wordpress.org/core/version-check/1.7/', false, $context );
	$data     = is_string( $response ) ? json_decode( $response, true ) : null;
	$version  = $data['offers'][0]['version'] ?? '';
	if ( ! is_string( $version ) || ! preg_match( '/^(\d+\.\d+)/', $version, $matches ) ) {
		return $readme;
	}

	$updated = preg_replace( '/^Tested up to: .*$/m', 'Tested up to: ' . $matches[1], $readme );

	return is_string( $updated ) ? $updated : $readme;
}

/**
 * @param string[] $lines
 */
function remove_lines( string $contents, array $lines ): string {
	foreach ( $lines as $line ) {
		$contents = str_replace( $line . "\n", '', $contents );
	}

	return $contents;
}

/**
 * @return string[]
 */
function files_to_update( string $projectRoot ): array {
	$fileNames      = [ 'Makefile', '.gitignore' ];
	$extensions     = [ 'php', 'js', 'css', 'json', 'md', 'txt', 'yml', 'yaml', 'dist', 'neon', 'pot' ];
	$skipDirectories = [ '.git', 'build', 'node_modules', 'vendor', 'vendor-prefixed' ];
	$files          = [];
	$iterator       = new RecursiveIteratorIterator(
		new RecursiveCallbackFilterIterator(
			new RecursiveDirectoryIterator( $projectRoot, FilesystemIterator::SKIP_DOTS ),
			static function ( SplFileInfo $file ) use ( $skipDirectories ): bool {
				return ! ( $file->isDir() && in_array( $file->getFilename(), $skipDirectories, true ) );
			}
		)
	);

	foreach ( $iterator as $fileInfo ) {
		if ( 'LICENSE' === $fileInfo->getFilename() ) {
			continue;
		}
		if ( in_array( $fileInfo->getFilename(), $fileNames, true ) || in_array( $fileInfo->getExtension(), $extensions, true ) ) {
			$files[] = $fileInfo->getPathname();
		}
	}
	sort( $files );

	return $files;
}

function run_composer( string $projectRoot, string $arguments ): void {
	$composer = getenv( 'COMPOSER_BINARY' );
	if ( is_string( $composer ) && '' !== $composer ) {
		$command = escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( $composer );
	} else {
		$command = 'composer';
	}

	$process = proc_open( $command . ' ' . $arguments, [ 0 => STDIN, 1 => STDOUT, 2 => STDERR ], $pipes, $projectRoot );
	if ( ! is_resource( $process ) ) {
		fail( "Failed to run composer {$arguments}." );
	}
	$exitCode = proc_close( $process );
	if ( 0 !== $exitCode ) {
		fail( "composer {$arguments} failed with exit code {$exitCode}.", $exitCode );
	}
}

function read_file( string $path ): string {
	$contents = file_get_contents( $path );
	if ( false === $contents ) {
		fail( "Failed to read {$path}." );
	}

	return $contents;
}

function write_file( string $path, string $contents ): void {
	if ( false === file_put_contents( $path, $contents ) ) {
		fail( "Failed to update {$path}." );
	}
}

function words_from_slug( string $slug, string $glue ): string {
	return implode( $glue, array_map( 'ucfirst', explode( '-', $slug ) ) );
}

function namespace_from_slug( string $slug ): string {
	$namespace = words_from_slug( $slug, '' );

	return ctype_alpha( $namespace[0] ) ? $namespace : 'Plugin' . $namespace;
}

function relative_path( string $path, string $root ): string {
	return ltrim( substr( $path, strlen( $root ) ), DIRECTORY_SEPARATOR );
}

function slugify( string $value ): string {
	$slug = strtolower( $value );
	$slug = preg_replace( '/[^a-z0-9]+/', '-', $slug );

	return trim( $slug ?? '', '-' );
}

/**
 * @return never
 */
function fail( string $message, int $exitCode = 1 ) {
	fwrite( STDERR, $message . "\n" );
	exit( $exitCode );
}
