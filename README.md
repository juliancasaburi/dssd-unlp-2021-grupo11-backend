# dssd-unlp-2021-grupo11-backend

## Comenzando 🚀

_Sigue las siguientes instrucciones para clonar este repositorio en tu máquina local_

### Pre-requisitos 📋

docker-compose
https://docs.docker.com/compose/install/

### Instalación 🔧

_Sigue las siguientes instrucciones para clonar el repositorio_

_Clone el repositorio_

```
git clone git@github.com:juliancasaburi/dssd-unlp-2021-grupo11-backend.git
```

_Posicionese sobre el directorio_

```
cd dssd-unlp-2021-grupo11-backend
```

_Configure el repositorio_

```
sudo chmod -R 777 storage bootstrap/cache
```

_Clone laradock_
```
git clone https://github.com/Laradock/laradock.git
```

_Configure laradock_
```
cd ./laradock
cp .env.example .env
```

_Inicie el servidor_
```
cd ./laradock
sudo docker-compose up -d nginx postgres workspace 
```

En el primer inicio, deberá instalar las dependencias y realizar algunas actividades de configuración
```
sudo docker-compose exec workspace /bin/bash
composer install
php artisan key:generate
php artisan jwt:secret
php artisan migrate
php artisan db:seed
```

_Si todo está correcto puede acceder al proyecto en la dirección http://localhost:80_ con los datos:

apoderado.test@acme.com  
grupo11

## Iniciar el servidor 🖥️ 🆙
Puede indicar los contenedores a iniciar. Como mínimo deberá iniciar nginx, postgres y workspace
```
cd ./laradock
sudo docker-compose up -d nginx postgres workspace
```

Por ejemplo, puede agregar redis y pgadmin

```
cd ./laradock
sudo docker-compose up -d nginx postgres redis pgadmin workspace
```
