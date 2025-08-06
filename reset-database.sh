#!/bin/bash

echo "Resetting database and removing Redis..."

echo "Stopping Docker containers..."
docker-compose down

echo "Removing database volume..."
docker volume rm jewelry_platform_mysql_data 2>/dev/null || true

echo "Starting containers without Redis..."
docker-compose up -d mysql app

echo "Waiting for MySQL to be ready..."
sleep 15

echo "Running database migrations..."
docker-compose exec app php artisan migrate:fresh --seed

echo "Database reset complete!"
echo "Admin user created: admin@jewelry.com / password123"
echo "Redis has been completely removed from the project."