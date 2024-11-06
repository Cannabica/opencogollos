# Version reducida

docker build . -t cannabica-app

docker run --rm -d --name cannabica-core -p 8000:8088 cannabica-app 

docker exec -it cannabica-core bash
        composer require fakerphp/faker:* --ignore-platform-req=ext-zip
        php artisan migrate
        php artisan db:seed

