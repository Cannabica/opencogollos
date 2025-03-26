#!/bin/bash

# Variables
VPS_IP=${VPS_IP:-"1.1.1.1"}
VPS_USER=${VPS_USER:-"us3r"}
SSH_KEY_PATH="$HOME/.ssh/cannabica_deploy"
DEPLOY_DIR="/var/www/cannabica"
DB_PASSWORD=${DB_PASSWORD:-"cannabica_password"}
DOCKERHUB_USERNAME=${DOCKERHUB_USERNAME:-"cannabica"}

# Generar clave SSH si no existe
if [ ! -f "$SSH_KEY_PATH" ]; then
    echo "Generando nueva clave SSH..."
    ssh-keygen -t ed25519 -f "$SSH_KEY_PATH" -N ""
fi

# Copiar clave SSH al servidor
echo "Copiando clave SSH al servidor..."
ssh-copy-id -i "$SSH_KEY_PATH.pub" "$VPS_USER@$VPS_IP"

# Crear archivo de configuración SSH
cat > ~/.ssh/config << EOF
Host cannabica-vps
    HostName $VPS_IP
    User $VPS_USER
    IdentityFile $SSH_KEY_PATH
    StrictHostKeyChecking no
EOF

# Configurar el servidor
echo "Configurando el servidor..."
ssh cannabica-vps << EOF
    # Actualizar sistema
    sudo apt update && sudo apt upgrade -y

    # Instalar Docker y Docker Compose
    curl -fsSL https://get.docker.com -o get-docker.sh
    sudo sh get-docker.sh
    rm get-docker.sh

    # Agregar usuario ubuntu al grupo docker
    sudo usermod -aG docker \$USER

    # Crear directorio de despliegue
    sudo mkdir -p $DEPLOY_DIR
    sudo chown -R \$USER:\$USER $DEPLOY_DIR

    # Crear archivo docker-compose.yml
    cat > $DEPLOY_DIR/docker-compose.yml << 'DOCKEREOF'
version: '3.8'

services:
  app:
    image: ${DOCKERHUB_USERNAME}/cannabica:latest
    restart: always
    ports:
      - "80:80"
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
      - DB_CONNECTION=pgsql
      - DB_HOST=db
      - DB_PORT=5432
      - DB_DATABASE=cannabica
      - DB_USERNAME=cannabica
      - DB_PASSWORD=${DB_PASSWORD}
      - CACHE_DRIVER=file
      - SESSION_DRIVER=file
      - QUEUE_CONNECTION=sync
      - ADMIN_EMAIL=REDACTADO
      - ADMIN_PASSWORD=${ADMIN_PASSWORD}
      - SEED_EXAMPLE_DATA=false
    depends_on:
      - db
    volumes:
      - ./storage:/var/www/html/storage

  db:
    image: postgres:15
    restart: always
    environment:
      - POSTGRES_DB=cannabica
      - POSTGRES_USER=cannabica
      - POSTGRES_PASSWORD=${DB_PASSWORD}
    volumes:
      - postgres_data:/var/lib/postgresql/data
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U cannabica"]
      interval: 10s
      timeout: 5s
      retries: 5

volumes:
  postgres_data:
DOCKEREOF

    # Crear archivo .env para docker-compose
    cat > $DEPLOY_DIR/.env << EOF
DOCKERHUB_USERNAME=${DOCKERHUB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}
ADMIN_PASSWORD=${ADMIN_PASSWORD}
EOF

    # Crear directorio para almacenamiento persistente
    mkdir -p $DEPLOY_DIR/storage
    chmod -R 775 $DEPLOY_DIR/storage
EOF

echo "Configuración completada. Para desplegar, ejecuta:"
echo "ssh cannabica-vps 'cd $DEPLOY_DIR && docker-compose pull && docker-compose up -d'" 