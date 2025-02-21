#!/bin/bash

# Nombre del archivo de salida
OUTPUT_FILE="project_structure.txt"

# Limpiar el archivo de salida si ya existe
> "$OUTPUT_FILE"

# 1. Obtener la estructura del proyecto con tree
echo "=== Estructura del Proyecto ===" >> "$OUTPUT_FILE"
tree -I "vendor|node_modules|storage|bootstrap|tests|database|public|resources|routes|docker|composer.json|package.json" -L 3 --dirsfirst >> "$OUTPUT_FILE"
echo -e "\n\n" >> "$OUTPUT_FILE"


echo "=== Proveedor del Panel de Filament ===" >> "$OUTPUT_FILE"
cat app/Providers/Filament/SuperadminPanelProvider.php >> "$OUTPUT_FILE"
echo -e "\n\n" >> "$OUTPUT_FILE"

# 3. Variables de entorno relevantes (sin credenciales)
echo "=== Variables de Entorno Relevantes ===" >> "$OUTPUT_FILE"
grep -E '^(APP_NAME|APP_ENV|FILAMENT_FILESYSTEM_DISK)=' .env >> "$OUTPUT_FILE"
echo -e "\n\n" >> "$OUTPUT_FILE"

# 4. Dependencias de Composer
echo "=== Dependencias de Composer ===" >> "$OUTPUT_FILE"
jq '.require' composer.json >> "$OUTPUT_FILE"
echo -e "\n\n" >> "$OUTPUT_FILE"

# 5. Últimos commits de Git
echo "=== Últimos 5 Commits ===" >> "$OUTPUT_FILE"
git log --oneline -n 5 >> "$OUTPUT_FILE"
echo -e "\n\n" >> "$OUTPUT_FILE"

# 6. Estado actual del repositorio
echo "=== Estado del Repositorio ===" >> "$OUTPUT_FILE"
git status >> "$OUTPUT_FILE"

# Mensaje de finalización
echo "Información del proyecto generada en $OUTPUT_FILE"