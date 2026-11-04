---
date: 2026-11-04
since: 4446f58 (sync.last)
head: 8877ff9
status: closed
---
# Conciliación 2026-11-04

## Resumen
`aidd.py drift` desde `4446f58` revisa 5 commits y marca 3 sin `(NNN)` ni `Ficha:`. Los 3 son un
solo cambio hecho fuera del flujo por decisión del usuario: las consultas del microservicio de
asistencias (PR #44). Añaden comportamiento, así que no caben en una ficha de carril. Tocan
archivos de siete specs `released`; ninguna contradice sus criterios. Cambian lo que dejaba fuera
la 021 (draft) y difieren del diseño de la 013 (`approved`, sin implementar). Sin huecos en
`architecture.md`, sin specs inferidas por validar ni fichas abiertas viejas.

## Commits fuera del flujo
| commit | qué es | propuesta | decisión |
|---|---|---|---|
| `fc3e495` feat(attendance): add /auth/me and internal appointment routes · `b892701` fix(attendance): give internal routes their own rate limiter · `de9a7d4` test(users): add auth.session.view to the role permissions matrix | Comportamiento nuevo: `GET /api/v1/auth/me` (solo administrador activo) y `GET /api/v1/internal/appointments/{today,{id}}` con llave de servicio y límite propio. El segundo commit corrige el límite antes del merge (la undécima lectura de QR del minuto habría recibido 429); el tercero añade el permiso nuevo a la matriz de `RolePermissionsTest`. Las rutas internas no usan `assertCan` (excepción a P5) | spec nueva 023 con `extends: [021, 014]` | spec 023 creada como registro (`draft`); se libera en v0.8.0 sin plan, tareas ni review, como 021 y 022 (decisión del usuario, 2026-11-04) |

## Specs con deriva
| spec | commits | archivos | ¿respeta sus criterios? | propuesta |
|---|---|---|---|---|
| 014 control de acceso y errores (`released`) | los 3 | `routes/api.php`, `bootstrap/app.php`, `CurrentActorAuthorizationService`, `RolePermissionsTest` | sí: `/auth/me` sigue `only.admin` + `assertCan` y el formato `{"error"}`; las rutas internas son una superficie nueva con su propia autenticación, sin cambiar las existentes | nada; la excepción a P5 queda en 023 |
| 015 primer despliegue (`released`) | `fc3e495`, `b892701` | `.env.example`, `config/services.php`, `bootstrap/app.php`, `AppServiceProvider` | sí: variable nueva opcional, vacía en `.env.example` y documentada en `deployment.md`; `EnvExampleDefaultsTest` en verde | nada |
| 016, 017, 018, 019, 020 (`released`) | los 3 | solo archivos compartidos (`bootstrap/app.php`, `routes/api.php`, `.env.example`, `AppServiceProvider`, `RolePermissionsTest`) | sí: sus rutas, permisos y pruebas no cambian | nada |

Specs no `released` que describen algo relacionado:
- **021 inicio de sesión de la app móvil (`draft`):** su "Fuera de alcance" remitía a "spec aparte" para `/auth/me` y las consultas internas; esa spec es la 023.
- **013 código QR por cita (`approved`, sin implementar):** describe un QR firmado, con caducidad y de un solo uso, validado por Dentissa. El microservicio usa hoy un QR con solo el identificador de la cita y valida él mismo. No se edita: hay que reconciliarla cuando se planee (`/specify --edit 013`).

## Arquitectura
Sin huecos.

## Specs inferidas
Ninguna sin validar.

## Fichas abiertas
Ninguna (`2026-10-15-fix-reprogramar-cita` está `escalated` a la spec 017, ya liberada).

## Cierre
El usuario pidió conciliar y liberar "como lo hicimos" con 021 y 022 (2026-11-04): la spec 023 queda en `draft` como registro del comportamiento liberado en v0.8.0. Las desviaciones de P1, P5 (rutas internas sin `assertCan`) y P14 quedan aceptadas por el usuario para esta versión. `sync.last` pasa a `8877ff9`.
