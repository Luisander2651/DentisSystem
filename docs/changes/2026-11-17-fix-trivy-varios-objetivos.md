---
lane: fix
status: done
base: d92623c
relates: []
covers: []
escalated_to: null
---

# Fix: el paso de trivy de `ai-dd.yml` falla siempre que corre

## Problema
En el PR #54 (release v0.11.0), el job "Seguridad (solo cambios del PR)" falló en el paso de trivy con `FATAL Fatal error multiple targets cannot be specified`. El paso solo corre cuando el PR toca `docker/` o `docker-compose.yml`, y este fue el primero en hacerlo desde que se activó el workflow (`8ccfd3b`). Así que el control de contenedores nunca había funcionado en CI. El job semanal "Seguridad (repo completo)" tiene el mismo comando.

## Reproducción
Con el comando exacto del workflow, en local:
`docker run --rm -v "$PWD:/src" -w /src aquasec/trivy:0.74.0 config --severity HIGH,CRITICAL --exit-code 1 docker/ docker-compose.yml`
→ `FATAL Fatal error multiple targets cannot be specified`

## Causa raíz
`trivy config` acepta un solo objetivo, `trivy config [flags] DIR`, y el workflow le pasaba dos: la carpeta `docker/` y el archivo `docker-compose.yml`. El comando se escribió así al activar el workflow y no se probó con un PR que tocara esas rutas.

## Cambio
`.github/workflows/ai-dd.yml`, en los dos pasos de trivy (el del PR y el semanal): se montan `docker/` y `docker-compose.yml` en una sola carpeta, `/scan`, en solo lectura, y se escanea esa carpeta como único objetivo. La severidad (`HIGH,CRITICAL`) y `--exit-code 1` no cambian.

## Escalado
Un solo archivo de configuración de CI. No toca código, contratos, datos ni seguridad de la aplicación, así que no hay que escalar.

## Verificación
- Antes: el comando del workflow termina con `FATAL … multiple targets cannot be specified`.
- Después, en local: `docker run --rm -v "$PWD/docker:/scan/docker:ro" -v "$PWD/docker-compose.yml:/scan/docker-compose.yml:ro" aquasec/trivy:0.74.0 config --severity HIGH,CRITICAL --exit-code 1 /scan` termina con código 0.
  - Detecta `docker/Dockerfile`, con 0 configuraciones erróneas altas o críticas.
  - `docker-compose.yml` no es un tipo que `trivy config` analice, igual que antes.
- **Review ligera:** la causa explica el síntoma, y el cambio conserva lo que se escanea y el umbral; no relaja el control.
- **En CI:** se confirma en el siguiente push del PR #54.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-17 | implícita | ¿Defecto o cambio de comportamiento? | Defecto evidente: el paso no podía terminar nunca | salida de trivy en el PR #54 |
