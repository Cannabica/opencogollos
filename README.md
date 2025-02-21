## Utilizar el dockerfile

hacemos build de depencias y temas
```
composer install
npm install
npm run build
docker build . -t cannabica-app
```

levantamos el container con el volumen asociado para la db 
`docker run --rm -d --name cannabica-core -v $(pwd)/cannabica_db.sqlite:/var/www/cannabica_db.sqlite  -p 8000:8088 cannabica-app` 

`docker run --rm -d --name cannabica-core -v $(pwd)/database/cannabica_db.sqlite:/var/www/database/cannabica_db.sqlite -v $(pwd)/.env:/var/www/.env -p 8000:8088 cannabica-app`

```

docker exec -it cannabica-app bash
        composer require fakerphp/faker:* --ignore-platform-req=ext-zip --no-progress --no-interaction
        php artisan migrate
        php artisan db:seed
```

## DB sqlite

```shell

#En el archivo de environment
DB_DATABASE=cannabica
DB_DATABASE=../database/cannabica_db.sqlite

```

## Credenciales de test
/superadmin
test@example.com
password

/tenant
user@tenant.com
password

## Notas
volar db, crearla y seedearla
`php artisan migrate:fresh --seed`

separados 
```
php artisan migrate:fresh
php artisan db:seed
```
uno especifico 
`php artisan db:seed --class=NombreDelSeeder`

