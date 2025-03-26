#!/bin/bash

# Nombre del archivo de salida
OUTPUT_FILE="project_structure.txt"

# Limpiar el archivo de salida si ya existe
> "$OUTPUT_FILE"

# 1. Obtener la estructura del proyecto con tree
echo "=== Estructura del Proyecto ===" >> "$OUTPUT_FILE"
tree -I "vendor|node_modules|storage|bootstrap|tests|database|public|resources|routes|docker|composer.json|package.json" -L 3 --dirsfirst >> "$OUTPUT_FILE"
echo -e "\n\n" >> "$OUTPUT_FILE"

# 2. Información de Docker
echo "=== Configuración de Docker ===" >> "$OUTPUT_FILE"
if [ -f "Dockerfile" ]; then
    echo "Dockerfile encontrado:" >> "$OUTPUT_FILE"
    cat Dockerfile >> "$OUTPUT_FILE"
else
    echo "No se encontró Dockerfile" >> "$OUTPUT_FILE"
fi
echo -e "\n\n" >> "$OUTPUT_FILE"

# 3. Variables de entorno relevantes (sin credenciales)
echo "=== Variables de Entorno Relevantes ===" >> "$OUTPUT_FILE"
grep -E '^(APP_NAME|APP_ENV|FILAMENT_FILESYSTEM_DISK|DB_CONNECTION|DB_HOST|DB_PORT|DB_DATABASE|DB_USERNAME|REDIS_HOST|REDIS_PASSWORD|REDIS_PORT)=' .env >> "$OUTPUT_FILE"
echo -e "\n\n" >> "$OUTPUT_FILE"

# 4. Dependencias de Composer
echo "=== Dependencias de Composer ===" >> "$OUTPUT_FILE"
jq '.require' composer.json >> "$OUTPUT_FILE"
echo -e "\n\n" >> "$OUTPUT_FILE"

# 5. Configuración de PostgreSQL
echo "=== Configuración de PostgreSQL ===" >> "$OUTPUT_FILE"
if command -v psql &> /dev/null; then
    echo "PostgreSQL está instalado" >> "$OUTPUT_FILE"
    psql --version >> "$OUTPUT_FILE"
else
    echo "PostgreSQL no está instalado" >> "$OUTPUT_FILE"
fi
echo -e "\n\n" >> "$OUTPUT_FILE"

# 6. Estado de Docker
echo "=== Estado de Docker ===" >> "$OUTPUT_FILE"
if command -v docker &> /dev/null; then
    echo "Docker está instalado" >> "$OUTPUT_FILE"
    docker --version >> "$OUTPUT_FILE"
    echo "Contenedores en ejecución:" >> "$OUTPUT_FILE"
    docker ps >> "$OUTPUT_FILE"
else
    echo "Docker no está instalado" >> "$OUTPUT_FILE"
fi
echo -e "\n\n" >> "$OUTPUT_FILE"

# 7. Últimos commits de Git
echo "=== Últimos 5 Commits ===" >> "$OUTPUT_FILE"
git log --oneline -n 5 >> "$OUTPUT_FILE"
echo -e "\n\n" >> "$OUTPUT_FILE"

# 8. Estado actual del repositorio
echo "=== Estado del Repositorio ===" >> "$OUTPUT_FILE"
git status >> "$OUTPUT_FILE"

# Mensaje de finalización
echo "Información del proyecto generada en $OUTPUT_FILE"