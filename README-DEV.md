# Solución para Problema de Permisos en Docker

## Problema Actual
- Los permisos de archivos/carpetas en el host están cambiando a www-data
- Esto ocurre porque:
  - El entrypoint.sh modifica permisos en /var/www/html
  - Los volúmenes montados propagan estos cambios al host

## Solución Propuesta

### 1. Modificar entrypoint.sh
- Eliminar líneas que cambian permisos en /var/www/html
- Solo configurar permisos en directorios internos del contenedor (/var/log/php-fpm)

### 2. Actualizar docker-compose.local.yml
- Usar volúmenes nombrados para:
  - storage/logs
  - storage/framework
- Mantener código fuente como volumen montado pero sin modificar permisos

### 3. Cambios específicos

#### entrypoint.sh
```diff
- chown -R www-data:www-data /var/www/html
- chmod -R 755 /var/www/html
- chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
- chmod -R 775 /var/www/html/public
```

#### docker-compose.local.yml
```yaml
volumes:
  laravel_storage:
  laravel_logs:

services:
  php:
    volumes:
      - .:/var/www/html
      - laravel_storage:/var/www/html/storage
      - laravel_logs:/var/www/html/storage/logs
```

## Pasos de Implementación
1. Aplicar cambios al entrypoint.sh
2. Modificar docker-compose.local.yml
3. Reconstruir y reiniciar contenedores