# Docker — Como Levantar el Contenedor para Desarrollo Local

Este archivo reúne los comandos más útiles para levantar los contenedores del proyecto DentissaApp.

## Requisitos
- Tener instalado Docker y Docker Compose (v2 preferible).
- En Windows es recomendable usar WSL2 para mejor compatibilidad con volúmenes.

## Levantar contenedore (desarrollo)
Construir imágenes y levantar en segundo plano:

```bash
docker compose up -d --build
```

## SuperUsuario
Dar Permisos de Super Usuario para Editar e Instalar Dependencias

```bash
docker compose exec -u root app chown -R www-data:www-data /var/www/html
```

## Instalacion de Dependencias
Instalar Dependencias Tanto de PHP Como de Node

```bash
docker compose exec app npm install
docker compose exec app composer install
```

## Levantar Servidor
Levantar Servidor con Host Reload de Vite Para Recarga de Servidor al Hacer Cambios en los Archivos

```bash
docker compose exec app npm run dev
```

## Acceso al Servidor
Abrir el Siguiente Enlace para Acceder al Contenido de la Pagina

<http://localhost:8000>

## Acceso al contenedor postgres de Docker
Ejecutar el siguiente comando en la terminal (CMD)

```bash
docker exec -it laravel-postgres psql -U admin -d mydb
```

## Migrar Base de datos
Ejecutar el siguiente comando en la terminal apra agregar las tablas al contenedor de la base de datos

```bash
docker exec app php artisan migrate
```