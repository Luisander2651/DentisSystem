---
id: 0001
status: accepted
date: 2026-09-22
---

# ADR 0001 · Registrar decisiones arquitectónicas mediante ADRs

## Contexto
Las decisiones de Dentissa están repartidas entre `ARCHITECTURE.md`, varios archivos
`PROPUESTA_*.txt/md` (ignorados por git) y los artefactos locales de AI-DLC en `aidlc-docs/`, que
tampoco se versionan. Eso ya produjo contradicciones (PHP 8.2 frente a 8.4, PHPUnit frente a Pest,
validación "por doctor" frente a "por fecha").

## Decisión
Toda decisión arquitectónica significativa se registra como ADR en `docs/adr/NNNN-<slug>.md`
usando `docs/templates/adr.md`. Un ADR aceptado no se edita: se reemplaza con otro que lo marque
`superseded by NNNN`. Son obligatorios para: dependencias nuevas (P10), dependencias nuevas entre
módulos (P3) y cualquier desviación de `docs/architecture.md`.

## Alternativas consideradas
- Seguir documentando en `ARCHITECTURE.md` — un solo archivo mutable pierde el porqué y la fecha de cada decisión.
- Solo mensajes de commit — no son localizables por tema.

## Consecuencias
- Positivas: historial trazable de decisiones versionado junto al código.
- Negativas: un paso más al introducir dependencias o cambiar la estructura.
