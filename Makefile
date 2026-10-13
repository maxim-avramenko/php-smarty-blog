COMPOSE_LOCAL = docker compose -f docker-compose.yml -f docker-compose.local.yml --env-file .env.local
COMPOSE_PROD = docker compose -f docker-compose.yml -f docker-compose.prod.yml --env-file .env.prod

.PHONY: up down logs up-prod down-prod db-schema db-seed css

up:
	$(COMPOSE_LOCAL) up -d --build

down:
	$(COMPOSE_LOCAL) down

logs:
	$(COMPOSE_LOCAL) logs -f

up-prod:
	$(COMPOSE_PROD) up -d --build

down-prod:
	$(COMPOSE_PROD) down

db-schema:
	$(COMPOSE_LOCAL) exec -T mysql sh -c 'mysql -u"$$MYSQL_USER" -p"$$MYSQL_PASSWORD" "$$MYSQL_DATABASE"' < sql/schema.sql

db-seed:
	$(COMPOSE_LOCAL) exec -T mysql sh -c 'mysql -u"$$MYSQL_USER" -p"$$MYSQL_PASSWORD" "$$MYSQL_DATABASE"' < sql/seed.sql

css:
	mkdir -p public/css
	docker run --rm -v "$(CURDIR):/src" -w /src node:22-alpine npx --yes sass assets/scss/app.scss public/css/app.css --no-source-map
