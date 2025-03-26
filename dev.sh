#!/bin/bash

# Colores para los mensajes
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Función para mostrar mensajes
print_message() {
    echo -e "${GREEN}==>${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}==>${NC} $1"
}

# Verificar si .env existe, si no, crearlo desde .env.example
if [ ! -f .env ]; then
    print_message "Creando archivo .env desde .env.example..."
    cp .env.example .env
    print_warning "Por favor, revisa y ajusta las variables en el archivo .env si es necesario"
fi

# Función para mostrar el menú
show_menu() {
    echo -e "\n${GREEN}=== Menú de Desarrollo ===${NC}"
    echo "1) Iniciar entorno de desarrollo"
    echo "2) Detener entorno de desarrollo"
    echo "3) Ver logs"
    echo "4) Ejecutar migraciones"
    echo "5) Ejecutar seeds"
    echo "6) Acceder al contenedor de la aplicación"
    echo "7) Acceder a la base de datos"
    echo "8) Reconstruir contenedores"
    echo "9) Limpiar todo (¡Cuidado! Eliminará la base de datos)"
    echo "q) Salir"
    echo -n "Selecciona una opción: "
}

# Función para manejar las opciones del menú
handle_option() {
    case $1 in
        1)
            print_message "Iniciando entorno de desarrollo..."
            docker-compose -f docker-compose.dev.yml up -d
            print_message "Entorno iniciado en http://localhost:8000"
            ;;
        2)
            print_message "Deteniendo entorno de desarrollo..."
            docker-compose -f docker-compose.dev.yml down
            ;;
        3)
            print_message "Mostrando logs (Ctrl+C para salir)..."
            docker-compose -f docker-compose.dev.yml logs -f
            ;;
        4)
            print_message "Ejecutando migraciones..."
            docker-compose -f docker-compose.dev.yml exec app php artisan migrate
            ;;
        5)
            print_message "Ejecutando seeds..."
            docker-compose -f docker-compose.dev.yml exec app php artisan db:seed --class=SuperAdminSeeder
            read -p "¿Deseas ejecutar los datos de ejemplo? (s/N): " run_example
            if [[ $run_example =~ ^[Ss]$ ]]; then
                docker-compose -f docker-compose.dev.yml exec app php artisan db:seed --class=ExampleDataSeeder
            fi
            ;;
        6)
            print_message "Accediendo al contenedor de la aplicación..."
            docker-compose -f docker-compose.dev.yml exec app bash
            ;;
        7)
            print_message "Accediendo a la base de datos..."
            docker-compose -f docker-compose.dev.yml exec db psql -U cannabica -d cannabica
            ;;
        8)
            print_message "Reconstruyendo contenedores..."
            docker-compose -f docker-compose.dev.yml up -d --build
            ;;
        9)
            read -p "¿Estás seguro? Esto eliminará todos los datos (s/N): " confirm
            if [[ $confirm =~ ^[Ss]$ ]]; then
                print_message "Limpiando todo..."
                docker-compose -f docker-compose.dev.yml down -v
                print_message "Limpieza completada"
            fi
            ;;
        q)
            print_message "¡Hasta luego!"
            exit 0
            ;;
        *)
            print_warning "Opción inválida"
            ;;
    esac
}

# Bucle principal
while true; do
    show_menu
    read option
    handle_option $option
done 