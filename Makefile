all: prefix archive 

.PHONY: prefix
prefix:
	composer install --no-dev
	vendor/bin/php-scoper add-prefix --config=./config/scoper.inc.php
	cd build && composer dump-autoload

.PHONY: archive
archive:
	mv build wpplugin
	zip -r wpplugin.zip wpplugin
	rm -rf wpplugin

.PHONY: test
test:
	vendor/bin/phpunit --bootstrap=./vendor/autoload.php tests
