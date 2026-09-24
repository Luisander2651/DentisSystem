# Changelog

Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/) y
[SemVer](https://semver.org/lang/es/). Lo mantiene `/release`.

## [Unreleased]

### Added
- Inicialización del flujo AI-DD: constitución, arquitectura, despliegue, seguridad, roadmap, ADRs y specs inferidas en `docs/`; validador en `.ai/bin/aidd.py`; workflow de CI `.ai/ci/ai-dd.yml`, guardado sin activar.

### Changed — flujo AI-DD actualizado a 1.6.0
- Validador 1.6.0 (`/analyze` delta con `aidd.py snapshot` y `changes`, cobertura de riesgos por corrección, aviso de specs grandes) y plantilla de spec con "Cobertura de riesgos"; `.ai/cache/` en `.gitignore`.
- Riesgos y brechas con IDs y estado por corrección: `RS1`–`RS13` en `security.md`, `OB1`–`OB11` en `observability.md` y `RD1`–`RD8` en `deployment.md` (contenido sin cambios; correcciones implícitas marcadas "derivada").
- Specs 013 y 014 con sección "Cobertura de riesgos"; objetivos 1, 4 y 5 del roadmap citan IDs.
- Sin copias `plan.vN.md`/`tasks.vN.md`: las versiones anteriores de la spec 014 quedan en git (`b8a71ce`, `ee1a146`).

### Changed — flujo AI-DD actualizado a 1.5.3
- Validador `.ai/bin/aidd.py` y plantillas de `docs/templates/` al día (secciones Auditoría y Observabilidad, `extends` en specs).
- Nuevo `docs/observability.md` (inferido) y bloque `observability` en `.ai/project.yaml`.
- Constitución 1.1.0: principio P14 (trazabilidad: correlación y auditoría).
- Roadmap: objetivos 4 y 5 ampliados con las brechas de observabilidad.
- `.ai/ci/ai-dd.yml` en modo baseline (sigue sin activar).

### Changed
- El flujo AI-DLC queda en pausa: sus reglas pasan de `CLAUDE.md` a `AIDLC.md`.
