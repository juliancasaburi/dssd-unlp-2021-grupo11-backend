# dssd-unlp-2021-grupo11-backend

# Comenzando 🚀

_Sigue las siguientes instrucciones para clonar este repositorio en tu máquina local_

### Pre-requisitos 📋

- docker-compose
https://docs.docker.com/compose/install/

- Haber clonado e instalado el docker-compose provisto por el grupo, siguiendo la guía de instalación https://github.com/juliancasaburi/dssd-unlp-2021-grupo11-laradock

- Haber clonado el proyecto BPM en Bonita Open Solution, siguiendo la guía de instalación https://github.com/juliancasaburi/dssd-unlp-2021-grupo11-bpm

### Instalación 🔧

_Sigue las siguientes instrucciones para clonar el repositorio_

_1. Posicionese sobre el directorio dssd-unlp-2021-grupo11-laradock_
```
cd ./dssd-unlp-2021-grupo11-laradock
```

_2. Posicionese sobre el directorio_

```
cd dssd-unlp-2021-grupo11-backend
```

_3. Configure el repositorio_

```
sudo chmod -R 777 storage bootstrap/cache
```

_4. Configure las variables de entorno_

Cree el archivo ` .env` a partir de ` .env.example`

```
cp .env.example .env
```

Configure las variables relacionadas a Bonita, Google Drive y CORS en el archivo ` .env`  

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

ESTAMPILLADO_ENDPOINT=http://localhost:82
```

> **NOTA:** Para obtener los datos de acceso a la API de Google Drive, se recomienda leer la siguiente guía https://robindirksen.com/blog/google-drive-storage-as-filesystem-in-laravel#setup-google-api-authentication

### En el primer inicio del servicio, deberá instalar las dependencias y realizar algunas actividades de configuración

Luego de iniciar el docker-compose provisto, deberá ejecutar los siguientes comandos

```
cd ./dssd-unlp-2021-grupo11-laradock/laradock
sudo docker-compose exec workspace /bin/bash
cd dssd-unlp-2021-grupo11-backend
composer install
php artisan key:generate
php artisan jwt:secret
php artisan migrate
```

## Database Seeding

_Para cargar los usuarios existentes en la aplicación de Bonita, siga los siguientes pasos_
>Es necesario que el servidor de Bonita se encuentre en ejecución
```
cd ./dssd-unlp-2021-grupo11-laradock/laradock
sudo docker-compose up -d nginx postgres workspace
sudo docker-compose exec workspace /bin/bash
cd dssd-unlp-2021-grupo11-backend
php artisan db:seed
```

# Accediendo a la api
La api puede accederse en http://localhost:80

# Endpoints - Documentación
La documentación generada por OpenAPI/Swagger, puede ser accedida en http://localhost:80/api/docs
> Nota: el listado de endpoints está completo, pero la funcionalidad try it out de los mismos aún no está completa para realizar pruebas.
