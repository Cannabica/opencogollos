## Utilizar el dockerfile

buildeamos
`docker build . -t cannabica-app`

levantamos el container con el volumen asociado para la db 
`docker run --rm -d --name cannabica-core -v $(pwd)/cannabica_db.sqlite:/var/www/cannabica_db.sqlite  -p 8000:8088 cannabica-app` 

`docker run --rm -d --name cannabica-core -v $(pwd)/cannabica_db.sqlite:/var/www/cannabica_db.sqlite -v $(pwd)/.env:/var/www/.env -p 8000:8088 cannabica-app`

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