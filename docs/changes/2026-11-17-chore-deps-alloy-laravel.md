---
lane: chore
status: done
base: 233dd68
relates: []
covers: []
escalated_to: null
---

# Chore: Alloy v1.1.1 y Laravel 12.72.0

## Problema
Dos dependencias existentes que hay que poner al día (roadmap → Pendientes, decisión del usuario del 2026-11-17):

- **`grafana/alloy` v1.1.0** (`docker-compose.prod.yml`): `loki.source.docker` se cae con un panic (`dockertarget.(*Target).Stop`) cuando un deploy recrea contenedores, y Alloy entra en un bucle de reinicios. En producción dejó de enviar logs del 2026-11-14 al 2026-11-17 (v0.11.0) y, tras desplegar v0.12.0, se reinició 659 veces y enviaba logs solo a ratos, así que Grafana no servía para vigilar los deploys.
- **`laravel/framework` 12.69.3**: aviso bajo CVE-2026-128083 (PKSA-yxbx-3c9s-cjt9, "Task Output Injection - Concurrency Process"), que afecta a versiones anteriores a 12.71.0. La app no usa `Concurrency` (026/R28), pero `composer audit` falla por él.

## Cambio
- `laravel/framework` v12.69.3 → v12.72.0 (`composer.lock`; `composer.json` sigue en `^12.0`). Solo cambia ese paquete: `composer update laravel/framework --with laravel/framework:12.72.0`, sin subir dependencias. v12.72.0 se publicó el 2026-11-10, así que cumple `min_package_age_days: 7`; la 12.73.0 (2026-11-17) no la cumple. Del changelog 12.70.0–12.72.0, lo que puede afectar: correos validados según el RFC 5321, reglas `sometimes` anidadas con `__missing__` y la caducidad de las URL firmadas temporales (corrección de seguridad). No hay cambios incompatibles. El script `boost:update` del `post-update-cmd` agotó su tiempo (red) y no tocó ningún archivo. Commit `ba58d20`.
- `grafana/alloy` v1.1.0 → v1.1.1 en `docker-compose.prod.yml`. La v1.1.1 (2024-05-30) solo trae correcciones, entre ellas "Fix a panic in `loki.source.docker` when trying to stop a target that was never started" (grafana/alloy#875). La configuración de Alloy no cambia. Commit `812bd93`.
- Cómo se aplica: Alloy no necesita versión nueva (`docs/deployment.md`: un merge que solo cambia `docker-compose.prod.yml` se aplica con `git pull` en el clon y `docker/prod/compose.sh up -d`). Laravel sale con la siguiente `/release`.

## Escalado
- Al empezar (`--files docker-compose.prod.yml composer.json composer.lock`): no escala.
- Al cerrar, sobre el diff real desde `233dd68` (`composer.lock`, `docker-compose.prod.yml`): no escala. Sin `--files`, `lane-check` contaba los archivos sin seguimiento del usuario (`nueva-landing/`, `Motion Graphics/`, `Memoria_de_estadia_Dentissa.docx`), que no son parte del cambio.

## Verificación
- Línea base: CI en verde en `f288b5b` (merge del PR #56); de ahí a `233dd68` solo cambió documentación.
- `./vendor/bin/pest --parallel` con Laravel 12.72.0: 2547 tests correctos (43 920 aserciones) en 195 s.
- SCA: `composer audit` sin avisos (antes: PKSA-yxbx-3c9s-cjt9, bajo). `npm audit` sigue con las 6 altas de `picomatch` de EX3; este chore no toca npm.
- Pint y SAST: no aplican, no hay PHP propio modificado. La imagen `grafana/alloy:v1.1.1` existe en Docker Hub (`docker manifest inspect`).
- Pendiente en producción: tras el `git pull` y `compose.sh up -d`, que el contador de reinicios de `dentissa-alloy` (`RestartCount` en `docker inspect`) se quede en 0 después de un deploy.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-17 | supuesto | ¿Laravel 12.71.0 o la última que cumple la antigüedad mínima? | 12.72.0: corrige el aviso e incluye la corrección de las URL firmadas temporales; la 12.73.0 todavía no cumple los 7 días | agente |
| 2026-11-17 | supuesto | `docker-compose.yml` (local) también usa Alloy v1.1.0 | No se toca: es ruta protegida y el roadmap pedía solo producción | agente |
