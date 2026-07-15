.PHONY: up down reset logs ps install key migrate seed fresh setup build test test-e2e test-all clean

up:
	docker compose up -d --build

down:
	docker compose down

reset:
	docker compose down -v

logs:
	docker compose logs -f app

ps:
	docker compose ps

install:
	docker compose exec app composer install --no-interaction

key:
	docker compose exec app php artisan key:generate

migrate:
	docker compose exec app php artisan migrate

seed:
	docker compose exec app php artisan db:seed

fresh:
	docker compose exec app php artisan migrate:fresh --seed

setup: up install key fresh

clean:
	docker compose exec app php artisan optimize:clear

build: clean
	docker compose exec app composer install --no-interaction --optimize-autoloader

test:
	docker compose exec app php artisan test --testsuite=Unit

test-e2e:
	docker compose exec app php artisan test --testsuite=Feature

test-all:
	docker compose exec app php artisan test
