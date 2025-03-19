#!/bin/bash

# Wait for a moment to ensure all services are ready
sleep 2

# Run database migrations and seeders
php artisan migrate:fresh --seed

# Start the application server
php artisan serve --host=0.0.0.0 --port=8088