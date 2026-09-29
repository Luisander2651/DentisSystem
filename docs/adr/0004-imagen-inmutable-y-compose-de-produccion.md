---
id: 0004
status: accepted
date: 2026-09-29
---

# ADR 0004 · Imagen de producción inmutable y Docker Compose separado para producción

## Contexto
Hasta la spec 015 un único `docker-compose.yml` servía para local y para el droplet, con el código
montado como volumen, Node en el contenedor de la app y los assets compilados a mano. Los cambios
hechos en el droplet para producción (TLS, puertos 80/443) rompieron el entorno local, y el
rollback dependía de recompilar dependencias y assets de la versión anterior.

## Decisión
- `docker/Dockerfile` pasa a ser multi-etapa: `dev` (el entorno actual, con Node y Composer),
  `prod` (código, `vendor/` sin dependencias de desarrollo y `public/build` copiados en la imagen,
  sin Node) y `web` (nginx con `public/` y la configuración TLS).
- Las imágenes se construyen en el droplet desde el tag y se etiquetan `dentissa-app:vX.Y.Z` y
  `dentissa-web:vX.Y.Z`. Rollback = arrancar las etiquetas de la versión anterior, sin recompilar.
- `docker-compose.yml` queda solo para local; `docker-compose.prod.yml` es un archivo completo e
  independiente, seleccionado en el droplet con `COMPOSE_FILE` en el `.env`.
- La configuración no versionada (`.env`) llega a los contenedores por `env_file` y variables de
  Compose; nunca se copia a una imagen.

## Alternativas consideradas
- Checkout montado con build en cada despliegue: más simple, pero el rollback recompila y el
  código del servidor se puede editar a mano.
- Un solo compose con override (`!reset`): menos duplicación, pero depende de Compose ≥ 2.24 y
  mezcla por defecto listas como `ports`, lo que puede reabrir puertos en producción.
- Registro de imágenes (GHCR, DOCR) y build en CI: se deja para cuando exista despliegue desde CI.

## Consecuencias
- Positivas: local y producción independientes; rollback de código en ~1 minuto; el servidor no
  tiene Node ni herramientas de desarrollo en ejecución.
- Negativas: dos archivos de Compose que mantener en paralelo; el build en el droplet consume
  memoria y tiempo; las imágenes antiguas ocupan disco hasta que se limpian.
