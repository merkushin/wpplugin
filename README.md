<!-- template:start -->
# merkushin/wpplugin

A template for WordPress plugins that are ready to submit to WordPress.org from the first commit:
tests, coding standards, static analysis, i18n, a JavaScript and CSS build, CI with the official
Plugin Check, a release workflow and instructions for coding agents.

```bash
composer create-project merkushin/wpplugin acme-widgets
```

The directory name becomes the plugin slug (`acme-widgets`), text domain and file name
(`acme-widgets.php`). The setup script asks for the plugin name, author and namespace, replaces
the template's placeholders and removes itself. Without a terminal it uses defaults, or the
`WPPLUGIN_*` environment variables listed in `scripts/post-create-project.php`.

The rest of this README becomes the new plugin's README.

<!-- template:end -->
# WP Plugin

Template for a new WordPress plugin

## Development

Requirements: PHP 7.4+, Composer, Node.js 22.22+, [WP-CLI](https://wp-cli.org/) for translations
and Docker for running Plugin Check locally.

```bash
make install     # composer install && npm ci
make check       # coding standards, PHPStan, PHP and JS tests, POT and version checks
make playground  # WordPress in the browser with this plugin active
npm start        # rebuild JavaScript and CSS on change
```

| Where | What |
| --- | --- |
| `wpplugin.php` | Plugin header and bootstrap |
| `src/` | PHP code, PSR-4 namespace `Wpplugin\` |
| `tests/unit/` | PHPUnit tests; WordPress calls are mocked through WPAL |
| `assets/src/admin/`, `assets/src/frontend/` | JavaScript and CSS entry points, built to `assets/build/` |
| `languages/` | Translation template, `make i18n` |
| `uninstall.php` | Cleanup when the plugin is deleted |
| `readme.txt` | The WordPress.org plugin page |
| `.wordpress-org/` | WordPress.org banners, icons, screenshots and Live Preview blueprint |

WordPress functions are called through [WPAL](https://github.com/merkushin/WPAL) services, so the
code can be unit-tested without WordPress. [AGENTS.md](AGENTS.md) has the conventions.

## Release

```bash
make dist          # builds wpplugin.zip
make smoke         # loads the built plugin and runs its hooks
make plugin-check  # WordPress.org's Plugin Check on the build
```

The first release goes through WordPress.org review: see
[docs/FIRST-RELEASE.md](docs/FIRST-RELEASE.md). After approval, pushing a version tag publishes
the plugin: see `.github/workflows/release.yml`.
