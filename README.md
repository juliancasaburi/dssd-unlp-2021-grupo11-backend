# dssd-unlp-2021-grupo11-backend

# Comenzando 🚀

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

_En el primer inicio, deberá instalar las dependencias y realizar algunas actividades de configuración_
```
sudo docker-compose exec workspace /bin/bash
composer install
php artisan key:generate
php artisan jwt:secret
php artisan migrate
```

_Configure las variables de entorno_

Configure las variables relacionadas a Bonita, Google Drive y CORS en el archivo .env.  

Ejemplo sin credenciales:

```
BONITA_API_URL=http://172.17.0.1:11775/bonita
BONITA_ADMIN_USER=
BONITA_ADMIN_PASSWORD=

FILESYSTEM_CLOUD=google
MAIN_GOOGLE_DRIVE_CLIENT_ID=
MAIN_GOOGLE_DRIVE_CLIENT_SECRET=
MAIN_GOOGLE_DRIVE_REFRESH_TOKEN=
MAIN_GOOGLE_DRIVE_FOLDER=DSSD-UNLP-2021-GRUPO11-BACKEND
MAIN_GOOGLE_DRIVE_PRIVATE_FOLDER=Privado
MAIN_GOOGLE_DRIVE_PUBLIC_FOLDER=Publico

FRONTEND_ENDPOINT=http://localhost:3002
```

## Database Seeding

_Para cargar los usuarios existentes en la aplicación de Bonita, siga los siguientes pasos_
>Es necesario que el servidor de Bonita se encuentre en ejecución
```
cd ./laradock
sudo docker-compose up -d nginx postgres workspace
sudo docker-compose exec workspace /bin/bash
php artisan db:seed
```

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

## Accediendo a la api
La api puede accederse en http://localhost:80

# Endpoints - Documentación
La documentación generada por OpenAPI/Swagger, puede ser accedida en http://localhost:80/api/docs

> Nota: el listado de endpoints está completo, pero la funcionalidad try it out de los mismos aún no está completa para realizar pruebas.
