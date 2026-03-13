.PHONY: setup up down rebuild remove ps stop artisan composer-install npm-install migrate seed fresh key storage-link fix-permissions

setup:
	@if [ ! -f .env ]; then cp .env.example .env; fi
	docker compose build
	docker compose up -d
	make key
	make fresh
	make storage-link
	make fix-permissions
	echo "Laravel project setup complete."

remove:
	docker compose down --rmi all --volumes --remove-orphans

rebuild:
	docker compose down
	docker compose build --no-cache
	docker compose up -d

up:
	docker compose up -d

down:
	docker compose down

ps:
	docker compose ps

stop:
	docker compose stop

composer-install:
	docker compose exec api composer install

npm-install:
	docker compose exec api npm install

migrate:
	docker compose exec api php artisan migrate

seed:
	docker compose exec api php artisan db:seed

fresh:
	docker compose exec api php artisan migrate:fresh --seed

key:
	docker compose exec api php artisan key:generate

storage-link:
	docker compose exec api php artisan storage:link

fix-permissions:
	docker compose exec api chown -R www-data:www-data storage bootstrap/cache
	docker compose exec api chmod -R 775 storage bootstrap/cache

test: 
	docker compose exec api php artisan test