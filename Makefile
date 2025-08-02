# Jewelry Platform Makefile

.PHONY: help build up down restart logs shell composer npm test clean backup

# Default target
help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-15s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

# Development commands
build: ## Build all Docker containers
	docker-compose build

up: ## Start all services
	docker-compose up -d

down: ## Stop all services
	docker-compose down

restart: ## Restart all services
	docker-compose restart

logs: ## Show logs for all services
	docker-compose logs -f

# Application commands
shell: ## Access Laravel application shell
	docker-compose exec app bash

composer: ## Run composer install
	docker-compose exec app composer install

npm: ## Install npm dependencies
	docker-compose exec frontend npm install

# Laravel commands
migrate: ## Run database migrations
	docker-compose exec app php artisan migrate

seed: ## Run database seeders
	docker-compose exec app php artisan db:seed

fresh: ## Fresh migration with seeding
	docker-compose exec app php artisan migrate:fresh --seed

key: ## Generate application key
	docker-compose exec app php artisan key:generate

cache: ## Clear all caches
	docker-compose exec app php artisan cache:clear
	docker-compose exec app php artisan config:clear
	docker-compose exec app php artisan route:clear
	docker-compose exec app php artisan view:clear

# Testing commands
test: ## Run PHP tests
	docker-compose exec app php artisan test

test-frontend: ## Run frontend tests
	docker-compose exec frontend npm test

# Production commands
prod-build: ## Build production containers
	docker-compose -f docker-compose.prod.yml build

prod-up: ## Start production services
	docker-compose -f docker-compose.prod.yml up -d

prod-down: ## Stop production services
	docker-compose -f docker-compose.prod.yml down

# Maintenance commands
clean: ## Clean up Docker resources
	docker system prune -f
	docker volume prune -f

backup: ## Create database backup
	docker-compose exec mysql mysqldump -u root -p$(DB_ROOT_PASSWORD) jewelry_platform > backups/backup_$(shell date +%Y%m%d_%H%M%S).sql

# Setup commands
setup: ## Initial setup for development
	cp .env.example .env
	make build
	make up
	make composer
	make npm
	make key
	make migrate
	make seed

setup-prod: ## Initial setup for production
	cp .env.example .env
	@echo "Please configure .env file for production"
	make prod-build
	make prod-up