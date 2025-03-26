#!/bin/bash

# Create a temporary build container
echo "Creating temporary build container..."
docker run --rm -v $(pwd):/app -w /app composer:latest composer install

echo "Installing npm dependencies and building assets..."
docker run --rm -v $(pwd):/app -w /app node:18 sh -c "npm install && npm run build"

echo "Building final Docker image..."
docker build . -t cannabica-app

echo "Build complete!"