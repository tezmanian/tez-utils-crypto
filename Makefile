PHP = docker compose run --rm php

.PHONY: install test phpstan cs-fix cs-check audit shell build

build:
	docker compose build

install:
	$(PHP) composer install

test:
	$(PHP) vendor/bin/phpunit --testsuite Unit --colors=always

phpstan:
	$(PHP) vendor/bin/phpstan analyse --no-progress --ansi --memory-limit=512M

cs-fix:
	$(PHP) vendor/bin/php-cs-fixer fix --ansi

cs-check:
	$(PHP) vendor/bin/php-cs-fixer fix --dry-run --diff --ansi

audit:
	$(PHP) composer audit

ci: audit phpstan cs-check test

shell:
	docker compose run --rm php sh
