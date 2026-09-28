# Dentissa

Sistema de gestión para una clínica dental: agenda de citas sin solapamientos, pacientes y
expediente clínico, seguimiento clínico con recetas, catálogo de tratamientos, gestión del staff y
contenido del sitio público, con confirmación de citas por WhatsApp. Lo usan el staff
administrativo y clínico; el portal del paciente aún no tiene pantallas. Etapa: **prototipo**, sin
datos reales ni producción.

> Antes de cualquier cambio lee [docs/constitution.md](docs/constitution.md). Si algo aquí
> contradice la constitución, prevalece la constitución.

## Stack
PHP 8.4 · Laravel 12 · Sanctum 4 · PostgreSQL 16 · Redis 7 (Predis) · Blade + JS vanilla + Tailwind 4
+ Vite 7 · Pest 3 + Eris (property-based) · Composer + npm · Docker Compose · tipo: `fullstack`

## Comandos
Todo se ejecuta dentro del contenedor (`phpunit.xml` apunta a los hosts `db` y `redis` de Docker).
```bash
docker compose up -d --build && docker compose exec app composer install && docker compose exec app npm install   # instalar
docker compose exec app php artisan migrate && docker compose exec app npm run dev                               # desarrollo local → http://localhost:8000
docker compose exec app ./vendor/bin/pest --parallel                                                             # tests
docker compose exec app vendor/bin/pint --dirty --format agent                                                   # lint/format
docker compose exec app npm run build                                                                            # build
```
Un solo archivo de tests: `docker compose exec app ./vendor/bin/pest tests/Modules/Users/Integration/RegisterUserTest.php`.

## Flujo de trabajo
1. `/specify` → spec en `docs/specs/NNN-<slug>/spec.md`
2. `/plan` → `plan.md` con Constitution Check
3. `/tasks` → `tasks.md`
4. `/analyze` → verificación independiente de consistencia → `analysis.md`
5. `/implement` → solo tareas aprobadas, una a la vez, con tests
6. `/review` → contra spec, plan y constitución → `review.md`
7. `/release` → versión, changelog y, con tu aprobación, producción

Usa `/clarify` para cerrar ambigüedades de una spec antes de aprobarla. Estado de todas las specs:
`python .ai/bin/aidd.py status`.

No implementes sin spec aprobada. No hagas commit sin que pasen los tests y Pint.
Nunca despliegues a producción sin aprobación explícita; sigue [docs/deployment.md](docs/deployment.md).
Nunca escribas secretos en el código, los logs ni los docs. No leas `.env`.

**Flujo AI-DLC en pausa.** Las Unidades 5–7 (ContentManagement, Email, whatsApp) quedaron a medias
en el flujo AI-DLC. Sus reglas están en [AIDLC.md](AIDLC.md) y su estado en
`aidlc-docs/aidlc-state.md` (no versionado). Solo se retoma si el usuario lo pide explícitamente
("retoma AI-DLC"); mientras tanto no sigas `AIDLC.md` ni escribas en `aidlc-docs/`.

## Reglas y estilo
- Módulo = `app/Modules/<Módulo>/{Domain,Aplication|Application,Infrastructure}`. Dentro de un módulo existente respeta su grafía (`Aplication`/`Application`, `Http`/`HTTP`); en módulos nuevos usa `Application` y `Http`. Clases en inglés; valores de dominio, rutas web y UI en español.
- Controladores invocables `final` de una acción → UseCase `final readonly` con `execute(DTO)` que llama primero a `assertCan()` → Domain Service → `*RepositoryInterface` enlazada en `AppServiceProvider`. DTOs `final readonly` con `::create()`; value objects `final readonly` que validan en `create()` y lanzan su excepción propia.
- Endpoints nuevos: FormRequest (reglas en array, mensajes en español), respuestas `{"message"}` / `{"error"}` / `{"data"}` con API Resources, y 500 siempre genérico (P6, P7). Migraciones dentro del módulo, con `down()`.
- Tests en `tests/Modules/<Módulo>/{Unit,Integration}` (no en `tests/Feature`), solo `it('...')` en inglés, base `<Módulo>IntegrationTestCase` declarada con `uses()`, helpers de `tests/Support` (`ActingAsStaff`, `ActingAsPatient`, `FakesTwilio`, `FakesBrevo`, `UsesEris`); propiedades en `*PropertiesTest.php`.
- Frontend: vista en `resources/views/pages/<sección>/`, JS en `resources/js/pages/<sección>/<acción>.js` registrado en `vite.config.js`; `fetch('/api/v1/...', { credentials: 'include' })`.
- Commits: Conventional Commits en inglés (`feat(users): …`); ramas `tipo/slug`; merge `--no-ff` a `main`.
- Las guías de Laravel Boost en `CLAUDE.md` aplican salvo donde esta sección o la constitución digan otra cosa (ubicación de tests, factories, estructura modular).

## Mapa del repositorio
| Ruta | Contenido |
|---|---|
| `app/Modules/` | Módulos de dominio: Auth, Users, Patients, Appointments, AppointmentTracking, ContentManagement, Email, whatsApp |
| `app/Core/` | Autorización (`assertCan`), middlewares `OnlyAdmin` e `InjectSanctumTokenFromCookie`, `UuidIdentifier` |
| `app/Providers/` | Bindings de repositorios, carga de migraciones por módulo, rate limiting, eventos |
| `routes/` | `api.php` (`/api/v1`), `web.php` (vistas Blade), `console.php` |
| `resources/views/`, `resources/js/pages/` | Vistas Blade y JS por página |
| `tests/Modules/`, `tests/Support/` | Suite Pest por módulo y helpers |
| `database/` | Migraciones técnicas, `RoleSeeder` |
| `docker/`, `docker-compose.yml` | Entorno Docker (app, nginx, postgres, redis, Loki/Grafana/Alloy) |
| `docs/` | Constitución, arquitectura, despliegue, seguridad, roadmap, specs, ADRs, plantillas |
| `.ai/` | `project.yaml` (configuración del flujo) y `bin/aidd.py` (validador) |

## Documentación
- [Constitución](docs/constitution.md) — principios innegociables
- [Arquitectura](docs/architecture.md)
- [Despliegue](docs/deployment.md) — entornos, deploy y rollback
- [Seguridad](docs/security.md) — datos sensibles, auth, herramientas y excepciones
- [Observabilidad](docs/observability.md) — logs, correlación, auditoría, métricas y brechas
- [Roadmap](docs/roadmap.md) — etapa actual y objetivos
- [Specs](docs/specs/README.md)
- [ADRs](docs/adr/)
- [Plantillas](docs/templates/)
