---
result: fail
round: 1
mode: full
constitution_version: 1.1.2
date: 2026-09-29
spec_sha: 962a94ef006f
plan_sha: a72ebc53aa4b
tasks_sha: f9a93b0d6fa0
---

# Análisis · 015 Primer despliegue en el VPS

## Resumen
**fail**, por 2 hallazgos CRÍTICOS:
- A1: `TwilioConection` se modifica, pero sigue registrando el teléfono y las variables de la
  plantilla (P11).
- A2: sin `.dockerignore`, el `.env` real y `public/hot` entrarían en la imagen.

Hay además 4 ALTOS de viabilidad: `vendor/` de la cola local, `storage/login.jpg` en la imagen
`web`, 403 frente a 404 con bloqueo del desafío ACME, y un test que no puede fallar.

La revisión en vivo de `dentissapp.com` (2026-09-29) añade 4 hallazgos:
- el sitio pasa por el proxy de Cloudflare, así que la IP del visitante y los puertos no se ven
  desde el dominio;
- Docker publica puertos por encima de `ufw`;
- la ruta real del repositorio en el droplet es otra;
- se expone `X-Powered-By`.

Conteo: 2 CRÍTICOS · 5 ALTOS · 17 MEDIOS · 7 BAJOS.

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 15 / 15 (CA3 y CA11 verificados en vivo solo en parte, A14) |
| Amenazas con control y test | 12 / 12 (falta una amenaza de secretos dentro de la imagen, A2) |
| Principios de la constitución evaluados | 14 / 14 (P11 ❌ por A1) |

## Seguimiento de rondas anteriores
Primera ronda.

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A1 | Constitución (P11) | CRÍTICA | plan P11; T028 → `TwilioConection::sendTemplate` | El método que se modifica (`env()` → `config()`) sigue registrando `'to'` (teléfono), `'json'` (variables con el nombre del paciente) y trazas. Con `stderr`, todo eso llega a Loki. | Decisión del usuario (2026-09-29): limpiarlo en la 015. Condición: `sendTemplate` no escribe teléfono, variables, nombre ni trazas. Test: `Log::spy()` sobre el adaptador real. → `/specify --edit` + `/plan --fix` + `/tasks --fix` |
| A2 | Seguridad | CRÍTICA | plan `Dockerfile`, T032, ADR 0004 | Sin `.dockerignore`, el build en el droplet copia `.env`, `.git`, `public/hot` y `bootstrap/cache/*.php` a las imágenes. | Condición: las imágenes `prod` y `web` no contienen `.env`, `.git`, `public/hot` ni cachés generadas fuera. Test: comprobación en T032 y en `verify.sh --local`; amenaza nueva en el modelo. → `/plan --fix`, `/tasks --fix` |
| A3 | Viabilidad | ALTA | T033 | La cola local con volúmenes anónimos propios queda con `vendor/` vacío. | Condición: `queue` local encuentra `vendor/autoload.php`. Test: `docker compose exec queue php artisan about` en T046. → `/plan --fix`, `/tasks --fix` |
| A4 | Viabilidad | ALTA | plan imagen `web`, volumen `storage` | `login`, `register` y `forgot-password` cargan `storage/login.jpg` (en `storage/app/public`, sin versionar); la imagen `web` no lo tiene y el volumen nuevo empieza vacío. | Condición: `/storage/login.jpg` 200 en producción y el contenido actual de `storage/app/public` se conserva. Test: `verify.sh --remote`. → `/plan --fix`, `/tasks --fix` |
| A5 | Inconsistencia | ALTA | plan `prod.conf`, T018, TM6 | `deny all` da 403, pero `verify.sh` espera 404. La regex de dotfiles tapa `/.well-known/acme-challenge/`. | Condición: un solo código esperado y el desafío ACME accesible. Test: `verify.sh --remote` y `certbot renew --dry-run`. → `/plan --fix`, `/tasks --fix` |
| A6 | Cobertura (P2) | ALTA | T016 | `fakeTwilio()` y `FakesBrevo` sustituyen la clase bajo prueba: el test no puede fallar antes del cambio. | Condición: el test instancia los adaptadores reales con valores solo en `config()`, sin red, y falla con `env()`. → `/tasks --fix` |
| A7 | Viabilidad | MEDIA | plan `config/database.php`, T026 | Con predis, `read_timeout` no aplica (usa `timeout` y `read_write_timeout`), y los reintentos alargan el fallo. | Condición: Redis caído → `/up` 500 en menos de 5 s con predis. Test: T012 con host y puerto inalcanzables. → `/plan --fix`, `/tasks --fix` |
| A8 | Viabilidad | MEDIA | plan Contratos `/up`, T027 | `/up` pinta `health-up` con 500 solo si el mensaje no está vacío, llama a `report($e)` (registra mensaje y traza) y relanza con debug. | Condición: mensaje no vacío y sin host; el log no contiene host ni el mensaje de PDO o Redis. Test: T012 con `app.debug=false` y `Log::spy()`. → `/plan --fix`, `/tasks --fix` |
| A9 | Viabilidad | MEDIA | plan `SecurityHeaders` (modo hot), CA1 | En modo hot, falta `connect-src ws://…:5173`: la CSP bloquearía la recarga en caliente. | Condición: con `npm run dev` no hay violaciones de CSP y hay recarga en caliente. Test: caso de T010 con `Vite::isRunningHot()` y T046. → `/plan --fix`, `/tasks --fix` |
| A10 | Inconsistencia | MEDIA | plan Rollout (interruptor CSP) | Con `env_file`, cambiar `.env` exige recrear el contenedor, no basta `optimize`. | Condición: el procedimiento documentado cambia la cabecera a `-Report-Only`. Test: ensayo en T052. → `/plan --fix`, `/tasks --fix` |
| A11 | Inconsistencia | MEDIA | T002 | La convención es `uses()` en cada archivo, no en `tests/Pest.php`. | Condición: `tests/Pest.php` sin cambios. → `/tasks --fix` |
| A12 | Ambigüedad | MEDIA | T020 | "El test unitario implícito de T010 compila" no se puede verificar. | Condición verificable (`php -l` y T010 sigue fallando solo por el registro). → `/tasks --fix` |
| A13 | Cobertura (RNF) | MEDIA | spec RNF "corte < 1 min" | Ninguna tarea mide el corte de un despliegue sin migraciones. | Medirlo en T054 (`deploy.sh ensayo-2`). → `/tasks --fix` |
| A14 | Cobertura | MEDIA | T052 | No verifica en vivo el correo de restablecimiento (CA3) ni PostgreSQL caído (CA11). | Añadir ambos a T052. → `/tasks --fix` |
| A15 | Ambigüedad | MEDIA | CA8, plan `.env.example` | `CACHE_STORE` sin valor fijado. `REDIS_PASSWORD=null` en la plantilla, con `requirepass ${REDIS_PASSWORD:?}`, deja la contraseña literal "null". | Condición: `CACHE_STORE`, `SESSION_DRIVER` y `REDIS_PASSWORD` coherentes con Compose. Test: `EnvExampleDefaultsTest`. → `/plan --fix`, `/tasks --fix` |
| A16 | Cobertura de riesgos | MEDIA | spec Cobertura, T030 | T030 aplica OB4.a y RS9.b sin que la spec los cite. | Decisión del usuario (2026-09-29): citarlos en la spec. → `/specify --edit` |
| A17 | Cobertura de riesgos | MEDIA | T042 | RS6.a pasa a mitigada sin depender del HSTS verificado (T031, T051). | El cambio de estado depende de la verificación remota. → `/tasks --fix` |
| A18 | Inconsistencia | MEDIA | spec Fuera de alcance, plan D4 | La spec dice que el certificado "no se rediseña", pero el plan pasa certbot a webroot. | Decisión del usuario (2026-09-29): recogerlo en la spec. → `/specify --edit` |
| A19 | Seguridad | MEDIA | plan, ADR 0004 | `node:20-alpine` está fuera de soporte; `composer:latest` y `nginx:alpine` no tienen versión fija. | Condición: imágenes base con versión fija y en soporte. Test: `ComposeFilesTest` o comprobación del `Dockerfile`. → `/plan --fix`, `/tasks --fix` |
| A20 | Ambigüedad | MEDIA | T037 | "Árbol limpio" no garantiza que `HEAD` sea el tag. | Condición: build solo si `HEAD` = tag. → `/tasks --fix` |
| A21 | Viabilidad | MEDIA | T035, T036 | En Windows no se pueden comprobar `/srv/…`, los permisos 700/600 ni `touch -d`. | Condición: ruta parametrizable y permisos verificados en Linux (contenedor o droplet). → `/tasks --fix` |
| A22 | Formato | BAJA | T022–T024 | `[P]` con dependencia pendiente. | Quitar `[P]`. → `/tasks --fix` |
| A23 | Cobertura | BAJA | TM12 | "Sin Composer" en la imagen no se verifica. | Añadir la comprobación a T032 y a `verify.sh`. → `/tasks --fix` |
| A24 | Ambigüedad | BAJA | T024 | `grep "<script>"` no detecta `<script type=…>`. | Buscar `<script` sin `nonce`. → `/tasks --fix` |
| A25 | Redacción | BAJA | spec Problema | "OB7 y OB8 parcialmente" cuando ninguna corrección entra. | Decisión del usuario (2026-09-29). → `/specify --edit` |
| A26 | Inconsistencia | BAJA | plan Contratos `/up` | La vista `health-up` carga CDN y fuentes externas, que la CSP bloquea; "página genérica" no es lo que devuelve. | Documentar el cuerpo real de `/up`. → `/plan --fix` |
| A27 | Inconsistencia | BAJA | T050 | Configura el cortafuegos, pero la spec y el plan dicen "se comprueba". | Unificar: comprobar. → `/tasks --fix` |
| A28 | Cobertura (revisión en vivo) | ALTA | spec, plan | `dentissapp.com` pasa por el proxy de Cloudflare (modo Full strict, confirmado por el usuario). La app ve la IP del proxy: el límite de 10/min por IP (`AppServiceProvider.php:196`) queda compartido entre usuarios y los logs registran IPs de Cloudflare. | Decisión del usuario (2026-09-29): criterio nuevo. Condición: el límite y los logs usan la IP real solo si la petición llega desde Cloudflare; una cabecera falsificada desde fuera no cambia la IP (abuso). → `/specify --edit` + `/plan --fix` + `/tasks --fix` |
| A29 | Verificabilidad (revisión en vivo) | MEDIA | CA9, T018, T019 | Por el dominio responde Cloudflare, así que los puertos se ven cerrados aunque no lo estén. Además Docker publica puertos por encima de `ufw`: el `3000:3000` del compose del droplet puede estar abierto en la IP directa aunque ufw solo permita 22, 80 y 443 (confirmado por el usuario). | Condición: CA9 se comprueba contra la IP directa del droplet. Test: `verify.sh --remote --origin <IP>`. → `/specify --edit` + `/tasks --fix` |
| A30 | Inconsistencia (revisión en vivo) | MEDIA | plan (`/srv/dentissa`), T035–T038 | El repositorio está en `/home/deploy/DentisSystem`, con el usuario `deploy`. | Rutas parametrizadas con esa ruta por defecto. → `/plan --fix`, `/tasks --fix` |
| A31 | Seguridad (revisión en vivo) | BAJA | plan `SecurityHeaders`, imagen `prod` | Las respuestas exponen `X-Powered-By: PHP/8.4.26`. | Condición: ninguna respuesta expone `X-Powered-By` ni la versión de nginx. Test: `SecurityHeadersTest` y `verify.sh --remote`. → `/plan --fix`, `/tasks --fix` |

## Decisiones pendientes del usuario
Ninguna: A1, A16, A18, A25 y A28 las decidió el usuario el 2026-09-29. El acceso solo desde Cloudflare
al origen no se incluye (el usuario confirma ufw con 22, 80 y 443).

## Aceptados
Ninguno: todos se corrigen en esta vuelta.
