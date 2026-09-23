# Functional Design Plan — Feature: QR por cita (Appointments)

## Plan Checklist

- [ ] Identificar lógica de negocio y reglas a cubrir con tests (generación atómica, firma, verificación/uso, cancelación, reprogramación y borrado)
- [ ] Identificar propiedades testeables para PBT (regla PBT-01, extensión activada con enforcement completo)
- [ ] Resolver preguntas de encuadre, actor, estados y tratamiento de los hallazgos
- [ ] Corregir `features/QRModule.md` con las decisiones de este plan (los hallazgos 5 a 9 contradicen supuestos de la spec)
- [ ] Generar `business-logic-model.md`, `business-rules.md`, `domain-entities.md`

---

## Contexto analizado

Fuentes: `features/QRModule.md` (spec aprobada), `aidlc-state.md`, `unit-1-appointments/code/summary.md`, `unit-1-appointments/functional-design/business-rules.md`, y el código de `app/Modules/Appointments`, `app/Modules/ContentManagement/StorageProvider.php`, `app/Core/Authorization`, `config/filesystems.php`, `routes/api.php`, `docker/Dockerfile` y `tests/Modules/Appointments`.

### Naturaleza del trabajo

A diferencia de las Units 0–5 (deuda técnica y tests sobre código existente), esta es una **feature nueva**: añade una tabla, un agregado y tres endpoints, y modifica tres flujos que ya tienen suite (Create, Update, Delete de Unit 1).

### Tamaño estimado

| Tipo | Nuevos | Modificados |
|---|---|---|
| Dominio (entidad, 3 VOs, 4 contratos, servicio, 2 excepciones) | 12 | 0 |
| Aplicación (3 DTOs, 4 use cases, 1 excepción) | 8 | 3 (`Create`, `Update`, `Delete` UseCase) |
| Infraestructura (migración, modelo, 2 repositorios, 2 adaptadores, 3 controllers, 1 resource) | 10 | 1 (`AppointmentModel`) |
| Transversal (`StorageProvider` + contrato, `AppServiceProvider`, `config/services.php`, `routes/api.php`, `.env.example`, `composer.json`, `phpunit.xml`) | 0 | 8 |
| **Total** | **30** | **12** |

### Superficie existente que toca la feature

| Pieza | Estado actual verificado | Impacto |
|---|---|---|
| `CreateAppointmentUseCase` | Guarda vía `AppointmentsService::saveAppointment()`, sin transacción | Pasa a `AppointmentSchedulingRepositoryInterface::schedule()` |
| `UpdateAppointmentUseCase` | Reprograma, cambia estado y recordatorio en un único `saveAppointment()` | Cancelación y reprogramación pasan a flujos con QR |
| `DeleteAppointmentUseCase` | Admin-only (`assertCan('appointments.delete')`), `destroy()` directo | Debe borrar el objeto de R2 |
| `ScheduledAppointment` → `CreatedAppointmentListener` | El listener es `ShouldQueue` y el evento se dispara **después** del use case | Correcto: se ejecuta tras el `COMMIT`. Sin cambios. |
| `StorageProvider` | Solo `saveImage(UploadedFile)` con nombre aleatorio | Necesita `saveContents()` y `urlFor()` |
| `docker/Dockerfile` | Extensión `gd` instalada | `endroid/qr-code` puede generar PNG sin cambios de imagen |
| `tests/.../AppointmentsIntegrationTestCase` | `createAppointment()` inserta directo por modelo, sin QR | Útil para simular citas previas a la feature |

### Rutas

Todas bajo `throttle:api` + `sanctum.cookie`, prefijo **`/api/v1`**:

- **Nuevas (`auth:sanctum`)**: `POST appointments/qr/verify`, `POST appointments/qr/use`, `GET appointments/{id}/qr`.
- **Modificadas indirectamente**: `POST appointments`, `PUT appointments/{id}`, `DELETE appointments/{id}`.

### Permisos

`CurrentActorAuthorizationService::assertCan()` exige `UserModel` activo y rol administrador para una lista cerrada de permisos. Hoy no existe ningún permiso para QR, y la spec no fija qué actor puede llamar a los endpoints nuevos (ver Pregunta 2).

---

## Hallazgos de comportamiento real (no se corrigen salvo aprobación en la Pregunta 3)

### Bloqueantes por extensión activada

1. **Un paciente autenticado podría consumir o leer QR ajenos.** La spec coloca los endpoints bajo `auth:sanctum` sin más. `PatientModel` también obtiene tokens Sanctum (ver `Tests\Support\ActingAsPatient`), así que:
   - `POST /qr/use` permitiría a un paciente marcar como `used` su propio QR (auto check-in) o cualquier otro del que tenga el payload.
   - `GET /appointments/{id}/qr` devolvería el QR **de cualquier cita** con solo conocer su UUID (IDOR), y con él el token firmado que da acceso a la cita.

   Es el mismo patrón que Unit 1 dejó documentado para Create/Update (“no hay enforcement explícito de tipo de actor”), pero aquí el recurso es precisamente una credencial de acceso. Control de acceso (**Security Baseline**).

2. **Ningún controller de Appointments valida la entrada con FormRequest.** Cero directorios `Requests` en `Appointments` y `AppointmentTracking`. Los tres controllers nuevos reciben un `payload` que hay que acotar (tipo, longitud, formato). **SECURITY-05** y la convención adoptada en Units 3 y 4.

3. **11 de los 15 controllers de Appointments filtran el mensaje interno en las respuestas 500** (`'message' => $e->getMessage()`), incluidos `CreateAppointmentController` y `UpdateAppointmentController`, que esta feature modifica. Con la feature, un fallo de R2 o de la librería QR expondría detalles de la infraestructura (endpoint, bucket, rutas). **SECURITY-15**. Idéntico al hallazgo corregido en Units 3, 4 y 5.

### Supuestos de la spec que el código no cumple

4. **El disco `s3` está configurado con `'throw' => false`.** `Storage::put()` y `Storage::delete()` **devuelven `false` en vez de lanzar excepción**. Consecuencias:
   - §7.1: si la subida a R2 falla, no se lanza nada → **la transacción hace `COMMIT`** y quedan una cita y un QR apuntando a una imagen que no existe. El rollback de la spec no se dispara nunca.
   - §7.2: `deleteImage()` ignora el valor de retorno → el `Log::warning` de objetos huérfanos **nunca se escribe**.

   `saveContents()` debe comprobar el `false` y lanzar (como ya hace `saveImage()` con `putFileAs`), y `deleteImage()` debe devolver o señalizar el fallo. Sin esto, la atomicidad de la spec es solo nominal.

5. **La base de datos es PostgreSQL, no MySQL.** `.env` y `phpunit.xml` usan `pgsql`; el diagrama de §7 dice MySQL. No cambia el diseño (Postgres soporta la transacción igual, y Laravel implementa el `enum` como `varchar` + `CHECK`), pero la spec debe corregirse.

6. **Las rutas viven bajo `/api/v1/…`, no `/api/…`** como indica §6.6. Además, la nota de “declarar `qr/*` antes de `/{id}`” no es necesaria: `POST /qr/verify` no colisiona con `GET|PUT|DELETE /{id}`. Es inocua, pero conviene quitarla para no inducir a error.

7. **`AppointmentEntity::cancel()` y `complete()` no tienen guardas.** Se puede cancelar una cita completada y cancelar dos veces. Con la spec actual:
   - Cancelar una cita cuyo QR ya está `used` → `markAsExpired()` desde `used` es transición inválida → **la cancelación falla** (409 o 500).
   - Cancelar dos veces → `expired → expired` también lanza.

   Hay que decidir si la cancelación solo toca QR `active` (idempotente) o si se prohíbe cancelar citas completadas.

8. **`reschedule()` tampoco tiene guardas**: pasa a `reprogramada` desde cualquier estado, incluido `cancelada`. Con la spec, reprogramar una cita cancelada **regeneraría un QR `active`** para ella, reactivando el acceso de una cita que se canceló.

9. **La spec repite dos defectos que ya se corrigieron en ContentManagement** (hallazgos 9 y 10 de Unit 5):
   - §5.2 (reprogramación): “elimina registro + objeto R2, genera y persiste uno nuevo”. Si la subida del nuevo falla, **se perdió el QR anterior**. Orden correcto: insertar el nuevo registro y subir la nueva imagen dentro de la transacción; borrar la imagen anterior **después** del `COMMIT`.
   - §5.3 (borrado): “borrar primero la imagen”. Si el `DELETE` en BD falla, la cita queda con un QR cuya imagen no existe. Orden correcto: borrar en BD y, tras el `COMMIT`, borrar en R2.

### Deuda funcional sin regla de extensión asociada

10. **Una cita completada conserva su QR `active`.** La spec dice “completada → sin cambios en el QR”, así que el QR puede usarse **después** de completar la cita. ¿Debe pasar a `used` (o `expired`) al completar desde `AppointmentTracking`?

11. **Citas existentes sin QR.** Las citas creadas antes del despliegue no tendrán registro. `GET /appointments/{id}/qr` responderá 404 para ellas, incluidas las futuras (`asignada`/`reprogramada`) a las que Android sí querría mostrar QR.

12. **La suite de Unit 1 se romperá al integrar la feature.** `CreateAppointmentTest` hará subidas reales a R2 si `AppointmentsIntegrationTestCase` no aplica `Storage::fake('s3')`, y `HmacQrSigner` lanzará en el constructor si `phpunit.xml` no define `QR_SIGNING_KEY`. Ambos cambios deben ir en la misma entrega.

13. **Dependencia nueva sin fijar.** `endroid/qr-code` no está en `composer.json`. El proyecto fija versiones exactas en sus dependencias de producción (`intervention/image 4.1.0`, `twilio/sdk 8.11.6`…); hay que fijarla igual y verificar que su resolución no arrastre de nuevo el *drift* de `composer.lock` que rompió los PBT en Unit 3.

> Los hallazgos 1, 4, 7, 8 y 12 se han verificado leyendo el código y la configuración. El 10 y el 11 son de definición de negocio y dependen de las Preguntas 4 y 5.

---

## Propiedades PBT identificadas (PBT-01)

| # | Componente | Categoría | Propiedad |
|---|---|---|---|
| P1 | `AppointmentQrStatus` | Invariante (dicotomía) | Para todo string, el VO devuelve un valor del catálogo (`active`, `used`, `expired`) o lanza excepción; nunca ambos ni ninguno |
| P2 | `AppointmentQrEntity` | Invariante (máquina de estados) | Para toda secuencia arbitraria de `markAsUsed()` / `markAsExpired()`, solo ocurren `active → used` y `active → expired`; un estado terminal **nunca** cambia |
| P3 | `HmacQrSigner` | Round-trip | Para todo UUID, `verify(sign(id))` devuelve exactamente ese id |
| P4 | `HmacQrSigner` | Invariante (integridad) | Para todo payload firmado y toda mutación (carácter cambiado, truncado, añadido, id intercambiado), `verify()` **nunca** devuelve un id |
| P5 | `HmacQrSigner` | Invariante (aislamiento de clave) | Para todo par de claves distintas K1 ≠ K2, un payload firmado con K1 **nunca** verifica con K2 |
| P6 | `HmacQrSigner` | Invariante (entrada arbitraria) | Para todo string arbitrario, `verify()` lanza una excepción controlada del dominio o `InvalidArgumentException`; nunca otro tipo de error |
| P7 | Payload | Invariante (sin datos personales) | Todo payload generado cumple `^[0-9a-f-]{36}\.[A-Za-z0-9_-]{43}$`: solo contiene id y firma |
| P8 | `EloquentAppointmentSchedulingRepository::schedule` | Invariante (atomicidad) | Para todo punto de fallo inyectado (insert de cita, insert de QR, subida devuelve `false`, subida lanza), o existen **las tres cosas** (cita, fila QR, objeto R2) o **ninguna** *(falla hoy según la spec — hallazgo 4)* |
| P9 | Creación | Invariante (unicidad) | Para N citas creadas, se generan N ids de QR, N `qr_url` y N payloads distintos |
| P10 | Reprogramación | Invariante (uno por cita, sin huérfanos) | Tras toda secuencia de reprogramaciones, cada cita tiene **exactamente una** fila QR y **exactamente un** objeto en R2 |
| P11 | `POST /qr/use` | Invariante (uso único) | Para k ≥ 1 llamadas con el mismo payload, exactamente **una** responde 200 y las k − 1 restantes 409 |
| P12 | `POST /qr/verify` | Invariante (sin efectos) | Para todo número de llamadas, el estado del QR no cambia |
| P13 | Cancelación | Invariante (idempotencia) | Cancelar una cita N veces deja el QR `expired`, sin imagen en R2 y **nunca** produce error *(falla hoy según la spec — hallazgo 7)* |
| P14 | `AppointmentQrEntity` | Round-trip (persistencia) | Para toda entidad válida, persistirla y reconstruirla con `fromPrimitives()` devuelve los mismos valores |
| P15 | Endpoints QR | Invariante (control de acceso) | Para todo actor fuera del definido en la Pregunta 2, los tres endpoints **nunca** responden 200 *(falla hoy según la spec — hallazgo 1)* |
| P16 | Endpoints QR | Invariante (sin 500) | Para todo cuerpo arbitrario, ningún endpoint QR responde **nunca** 500 |

Componentes **sin propiedades PBT** (se cubren con ejemplos, PBT-10): DTOs, fábricas de excepción, `AppointmentQrResource`, `EndroidQrCodeGenerator` (se verifica que devuelve un PNG decodificable, sin propiedad propia) y el registro de bindings.

---

## Preguntas de Clarificación

### Question 1 — Encuadre dentro del ciclo AI-DLC

La feature no está en `unit-of-work.md` y Unit 5 (ContentManagement) está en curso, pendiente de respuestas.

A) **Registrarla como Unit 8** en `unit-of-work.md` y ejecutarla **después** de cerrar Unit 5, siguiendo el mismo loop (Functional Design → Code Generation).

B) **Registrarla como Unit 8 y pausar Unit 5** para ejecutarla ya. Unit 5 se retoma después.

C) **Tratarla como feature independiente**, fuera del ciclo de deuda técnica, con sus propios artefactos en `aidlc-docs/construction/feature-appointment-qr/`, sin modificar `unit-of-work.md`.

D) Other (please describe after [Answer]: tag below)

[Answer]:

---

### Question 2 — Quién puede usar los endpoints QR

Define el alcance del hallazgo 1 y de la propiedad P15.

A) **Solo staff**: los tres endpoints exigen `UserModel` activo (cualquier rol). La app Android es una herramienta del personal: el paciente presenta el QR y el staff lo escanea.

B) **Staff para verificar y usar; el paciente puede leer solo el QR de sus propias citas** en `GET /appointments/{id}/qr` (403 si la cita no es suya). La app Android la usan ambos.

C) **Como B, pero `use` restringido a administradores**, vía un permiso nuevo `appointments.qr.use` en `CurrentActorAuthorizationService`.

D) Other (please describe after [Answer]: tag below)

[Answer]:

---

### Question 3 — Tratamiento de los 13 hallazgos

Los hallazgos **1 a 3** incumplen reglas de la extensión **Security Baseline** (bloqueante). El **4** invalida la atomicidad, que es el requisito central de la spec.

A) **Implementar la spec tal cual** y documentar los 13. Exige aceptar formalmente los 3 incumplimientos de la extensión y que la atomicidad no se cumple.

B) **Corregir los bloqueantes 1–3 y el 4** en el código nuevo (control de actor, FormRequests en los 3 controllers nuevos, 500 genérico en los controllers nuevos y en `Create`/`Update`, `StorageProvider` que lance ante `false`). Documentar el resto.

C) **B más las correcciones de diseño 7, 8, 9 y 12** (reglas de estado ante cancelar/reprogramar, orden seguro de borrado y regeneración, ajustes de la suite de Unit 1). Todas se limitan a los flujos que esta feature ya modifica.

D) **C más extender el hallazgo 3 a los 11 controllers de Appointments** y añadir FormRequests a `CreateAppointmentController` y `UpdateAppointmentController`. Amplía el alcance a código que la feature no necesita tocar.

E) Other (please describe after [Answer]: tag below)

[Answer]:

---

### Question 4 — Reglas de estado de la cita frente al QR

Si los hallazgos 7, 8 y 10 entran en alcance, hay que fijar la regla exacta.

A) **Solo se actúa sobre QR `active`**:
   - Cancelar: si el QR está `active` → `expired` + borrar imagen; si ya es `used`/`expired` → no se toca y la cancelación procede (idempotente).
   - Reprogramar una cita `cancelada` o `completada` → 409, no se regenera QR.
   - Completar (AppointmentTracking): el QR no cambia.

B) **Como A, pero completar marca el QR como `used`** si seguía `active`, dentro de la transacción de `EloquentAppointmentCompletionRepository::complete()`. Extiende el alcance a `AppointmentTracking`.

C) **Como A, pero completar marca el QR como `expired`** y borra su imagen de R2 (misma lógica que cancelar).

D) Other (please describe after [Answer]: tag below)

[Answer]:

---

### Question 5 — Citas creadas antes de la feature

Hallazgo 11.

A) **Sin backfill**: solo las citas nuevas tienen QR. `GET /appointments/{id}/qr` responde 404 para las anteriores.

B) **Comando Artisan de backfill** (`appointments:generate-missing-qr`) que genere QR para las citas `asignada`/`reprogramada` con fecha ≥ hoy. Se ejecuta una vez tras el despliegue; es idempotente.

C) **Generación bajo demanda**: `GET /appointments/{id}/qr` genera el QR si la cita es futura y no lo tiene. Evita el comando, pero convierte un `GET` en una operación con escritura.

D) Other (please describe after [Answer]: tag below)

[Answer]:

---

### Question 6 — Escenarios de test a cubrir

¿Cuáles son imprescindibles? (marca todos los que apliquen)

A) **Creación atómica con `Storage::fake('s3')`**: cita + fila QR + PNG en la ruta determinista; rollback completo cuando falla el insert del QR, cuando la subida lanza y cuando la subida devuelve `false`

B) **Firma**: round-trip, alteración del payload, clave distinta, clave vacía, payload mal formado (PBT P3–P7)

C) **Verificación y uso**: `verify` sin efectos, `use` único, estados `used`/`expired` con 409/410

D) **Cancelación, reprogramación y borrado**: estado del QR, imagen eliminada de R2, orden seguro ante fallos, citas sin QR

E) **Control de acceso**: los tres endpoints con staff activo, staff inactivo, paciente (propio y ajeno según la Pregunta 2) y sin autenticar (401)

F) **Regresión de Unit 1**: la suite existente de Appointments sigue en verde tras integrar la feature

G) **Unit tests de Value Objects y entidad** aislados de base de datos (estado, URL, máquina de estados)

H) Other (please describe after [Answer]: tag below)

[Answer]:

---

### Question 7 — Resguardo del código de producción

Igual que en Units 3 y 4:

A) **Rama dedicada `feat/appointment-qr`** desde `main`, con commit y merge al aprobar.

B) **Rama dedicada + tag de resguardo** sobre el `main` actual.

C) Other (please describe after [Answer]: tag below)

[Answer]:

---

## Siguiente paso

Responde los `[Answer]:` de arriba. Analizaré las respuestas en busca de ambigüedades y, si las hay, abriré un archivo de clarificación antes de corregir `features/QRModule.md` y generar los artefactos de diseño funcional.