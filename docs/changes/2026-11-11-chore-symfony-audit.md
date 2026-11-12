---
lane: chore
status: done                 # open → done → released · escalated (→ escalated_to)
base: f1829fa                # commit de partida; lo registra la skill al empezar (lo usa lane-check)
relates: []                  # specs cuyo comportamiento toca ([] si ninguna)
covers: []
escalated_to: null           # NNN de la spec si el cambio se escaló al flujo completo
---

# Chore: Symfony a 7.4.23 por los avisos de composer audit

## Problema
`composer audit` da 8 avisos de Symfony (`symfony/filesystem`, `symfony/http-foundation` ×4 con
CVE-2026-118514, `symfony/http-kernel`, `symfony/routing` con CVE-2026-118516 y `symfony/yaml` con
CVE-2026-123792), corregidos en 7.4.23 (publicada el 2026-11-04). Hasta hoy esa versión no
cumplía la antigüedad mínima de 7 días (`security.agent.min_package_age_days`), así que la v0.9.0
salió con la excepción EX2 de `docs/security.md` (vence el 2026-11-12) y los 8 identificadores en
`composer.json` → `config.audit.ignore` para que el check de SCA de CI no bloqueara. Este chore
sube los paquetes dentro de los rangos que ya existen, quita esos `ignore` y cierra EX2.

## Cambio
- `composer update symfony/filesystem symfony/http-foundation symfony/http-kernel symfony/routing symfony/yaml`,
  dentro de los rangos de `composer.json`: `symfony/filesystem` v8.1.6 → v8.1.11,
  `symfony/http-foundation` v7.4.20 → v7.4.23, `symfony/http-kernel` v7.4.20 → v7.4.23,
  `symfony/routing` v7.4.20 → v7.4.23 y `symfony/yaml` v7.4.18 → v7.4.23 (`composer.lock`).
- `composer.json`: fuera `config.audit.ignore` con los 8 `PKSA-…` de EX2.
- `docs/security.md`: EX2 cerrada. `CHANGELOG.md`: línea en `[Unreleased]` → Security.
- Verificación del paquete (`agent-security.md` §2): mismos paquetes y origen (Packagist,
  `symfony/*`); v7.4.23 y v8.1.11 publicadas el 2026-11-04, 7 días hoy
  (`min_package_age_days: 7`).
- Changelogs de v7.4.21, v7.4.22, v7.4.23 y v8.1.11 (GitHub releases): sin cambios incompatibles
  declarados; solo correcciones y seguridad. Lo que cambia de comportamiento y se comprobó en el
  código: `#[MapRequestPayload]` deja de decodificar CSV y YAML (no se usa), `Request::getBaseUrl()`
  y los rangos privados como proxies de confianza (la app confía solo en los rangos públicos de
  Cloudflare de `config/security.php`, sin `PRIVATE_SUBNETS`) y los placeholders de host en las rutas
  (no hay rutas con host). Laravel Boost reescribió la versión de PHP de `CLAUDE.md` al terminar
  `composer update`; se deshizo para no salirse del chore.

## Escalado
- Al empezar (`composer.json`, `composer.lock`, `docs/security.md`): no escalar, cabe en el carril.
- Al cerrar, sobre el diff real desde `f1829fa`: no escalar (3 archivos de código). Sin dependencias
  nuevas: solo versiones de paquetes que ya estaban.

## Verificación
- Línea base en `main` (`pest --parallel`): 2017 pasan, 3 intermitentes de la línea base (conexión a
  la base de datos en `RoleCopyTest` y `NotFoundPageTest`, `DeployScriptsBehaviourTest`).
- Tras el cambio, `pest --parallel`: la primera vuelta dio 157 fallos, todos de la base de datos de
  un proceso paralelo ("relation \"treatments\" does not exist" tras tiempos de espera de conexión),
  ajenos a Symfony; la segunda, 2019 pasan y 1 tiempo de espera de conexión
  (`ReportsAccessControlTest`), que en serie pasa (21). Sin fallos nuevos.
- SCA: `composer audit` sin los `ignore` daba 8 avisos; tras la actualización, "No security
  vulnerability advisories found". Sin cambios en `package.json`.
- Pint y SAST: sin cambios de código PHP propio.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-09 | diseño | Liberar la v0.9.0 con los avisos abiertos | Excepción EX2 hasta el 2026-11-12 y este chore en cuanto 7.4.23 cumpla 7 días | usuario |
