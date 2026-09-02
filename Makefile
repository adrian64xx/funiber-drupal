# ==============================================================================
# FUNIBER Drupal 11 - Makefile
# ==============================================================================

.PHONY: help up down restart build logs sh drush cr install test lint status

help: ## Muestra esta ayuda
	@echo "Comandos disponibles:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}'

up: ## Levanta los contenedores en segundo plano
	docker compose up -d
	@echo "\n✅ Sitio disponible en: http://localhost:8080"

down: ## Detiene los contenedores
	docker compose down

restart: down up ## Reinicia todos los contenedores

build: ## Construye o reconstruye las imágenes de Docker
	docker compose build --no-cache

logs: ## Muestra los logs en tiempo real
	docker compose logs -f

sh: ## Accede a la terminal del contenedor de Drupal
	docker compose exec -u www-data drupal bash

install: ## Instala las dependencias de Composer e inicializa Drupal
	docker compose exec -u www-data drupal composer install
	docker compose exec -u www-data drupal drush site:install standard \
		--db-url=mysql://drupal:drupal_secret_pass@database:3306/drupal11 \
		--site-name="FUNIBER News Portal" \
		--account-name=admin \
		--account-pass=admin1234 \
		--account-mail=admin@funiber.org -y
	docker compose exec -u www-data drupal drush theme:enable funiber_theme -y
	docker compose exec -u www-data drupal drush config:set system.theme default funiber_theme -y
	docker compose exec -u www-data drupal drush pm:enable funiber_tech_news -y
	docker compose exec -u www-data drupal drush cr
	@echo "\n🎉 Instalación completada! Credenciales: admin / admin1234"

drush: ## Ejecuta comandos drush (ej: make drush cmd="status")
	docker compose exec -u www-data drupal drush $(cmd)

cr: ## Limpia la caché de Drupal
	docker compose exec -u www-data drupal drush cr

test: ## Ejecuta las pruebas unitarias automatizadas con PHPUnit
	docker compose exec -u www-data drupal vendor/bin/phpunit web/modules/custom/funiber_tech_news/tests

lint: ## Ejecuta el linter con estándares Drupal y PSR-12
	docker compose exec -u www-data drupal vendor/bin/phpcs --standard=Drupal,DrupalPractice web/modules/custom web/themes/custom

status: ## Muestra el estado de los contenedores
	docker compose ps
