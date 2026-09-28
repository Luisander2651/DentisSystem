---
id: 013
slug: codigos-qr-por-cita
status: approved
created: 2026-09-22
extends: [014]
---

# 013 · Código QR por cita para el registro automático de entrada

> Migrada por `/init` desde `features/QRModule/spec.md` (diseño técnico) y `features/QRModule/plan.md`
> (plan funcional AI-DLC con preguntas sin responder). Ambos se conservan en
> [referencias/](referencias/) como insumo para `/plan`, pero **el diseño original quedó superado**
> por las aclaraciones del 2026-09-22: el QR lo tiene el paciente y la entrada la registra un
> lector fijo automático, no personal con una app.

## Problema
La clínica quiere que el paciente registre su llegada sin intervención del personal: el paciente
muestra en su app el QR de su cita, un lector fijo en recepción lo escanea y el sistema valida y
registra la entrada automáticamente. Hoy la cita solo existe en el panel web y no hay forma
verificable de registrar la llegada. Además, esta spec atiende **parcialmente** RS1 de `docs/security.md` (correcciones RS1.b y RS1.c; RS1.a y RS1.d las cubre la spec 014).

## Historias de usuario
- Como paciente, quiero ver en mi app el QR de mis próximas citas para presentarlo al llegar.
- Como paciente, quiero que al escanear mi QR en recepción mi entrada quede registrada sin hacer fila.
- Como clínica, quiero que un QR solo sirva una vez, el día de su cita, y que no queden imágenes inútiles almacenadas.

## Criterios de aceptación
- [ ] CA1 · Dada una cita creada con éxito, cuando termina su creación, entonces existe exactamente un QR `active` asociado a ella y su imagen está almacenada de forma privada.
- [ ] CA2 · Dado un fallo al generar, registrar o almacenar el QR, cuando se crea la cita, entonces no queda ni la cita ni el QR ni la imagen (todo o nada).
- [ ] CA3 · Dado un paciente autenticado, cuando pide el QR de una de sus citas, entonces recibe `id`, `appointment_id`, `status`, `created_at` y un enlace a la imagen que caduca a los pocos minutos; cada petición genera un enlace nuevo.
- [ ] CA4 · Dado un QR `active` de una cita de **hoy**, cuando el lector de recepción envía el código leído, entonces el sistema responde "acceso concedido", el QR pasa a `used`, la entrada queda registrada con fecha, hora y lector, y la imagen se elimina del almacenamiento; el registro del QR se conserva.
- [ ] CA5 · Dado un QR `active` de una cita de **otro día** (anterior o posterior), cuando el lector lo envía, entonces se rechaza con "fuera de fecha" y el QR sigue `active`.
- [ ] CA6 · Dada una cita reprogramada, cuando cambia su fecha u hora, entonces el QR anterior deja de ser válido y se emite uno nuevo `active`.
- [ ] CA7 · Dada una cita cancelada o marcada como completada sin haber registrado su entrada, cuando cambia a ese estado, entonces su QR pasa a `expired` y su imagen se elimina; el registro se conserva.
- [ ] CA8 · Dada una cita eliminada, cuando se borra, entonces se eliminan su QR y su imagen.
- [ ] CA9 · Dada una cita anterior a esta feature (sin QR), cuando se cancela, completa o se pide su QR, entonces no hay error (la consulta responde "no encontrado").
- [ ] CA10 · Dado un QR `expired` o sin imagen, cuando el paciente pide su QR, entonces recibe el estado y un enlace vacío.
- [ ] CA11 · Dado un fallo al eliminar la imagen tras usar, cancelar o completar, cuando ocurre, entonces el cambio de estado se mantiene y el fallo queda registrado para limpieza posterior.
- [ ] CA12 · (abuso) Como atacante, altero el contenido del QR o lo firmo con otra clave → el lector recibe "rechazado" (firma inválida).
- [ ] CA13 · (abuso) Como atacante, envío un contenido mal formado (sin separador, id que no es UUID, longitud excesiva) → se rechaza como entrada inválida sin error interno.
- [ ] CA14 · (abuso) Como paciente, presento el mismo QR dos veces, o una captura de un QR ya usado → el segundo intento se rechaza ("ya usado").
- [ ] CA15 · (abuso) Como atacante, presento un QR `expired` → se rechaza.
- [ ] CA16 · (abuso) Como paciente autenticado, pido el QR de una cita que no es mía → se rechaza igual que si no existiera.
- [ ] CA17 · (abuso) Como atacante, envío peticiones de registro de entrada desde un dispositivo no registrado por la clínica, o sin credencial del dispositivo → se rechazan y no cambian el estado de ningún QR.
- [ ] CA18 · (abuso) Como atacante en la red, reenvío una petición del lector capturada previamente → se rechaza (el QR ya está usado o la petición ya no es válida).
- [ ] CA19 · (abuso) Como atacante, envío muchas peticiones de registro para adivinar códigos → se limitan por dispositivo y se registran.
- [ ] CA20 · (abuso) Como observador en recepción, leo la respuesta del lector → solo indica concedido o el motivo del rechazo; nunca incluye datos personales ni clínicos del paciente.
- [ ] CA21 · (abuso) Sin autenticar, pido el QR de una cita → 401.
- [ ] CA22 · (abuso) Como atacante, provoco un fallo del almacenamiento o de la generación del QR → la respuesta no revela detalles de la infraestructura.
- [ ] CA23 · (abuso) Como paciente autenticado, uso mi token en cualquier ruta de staff (pacientes, expedientes, agenda, citas, usuarios, contenido) → la autenticación de staff lo rechaza por sí misma, sin depender del middleware de staff de la spec 014. Y un token de staff no sirve en las rutas de paciente. Los pacientes y el staff se autentican por separado (RS1.c de `docs/security.md`).
- [ ] CA24 · (abuso) Como paciente autenticado, intento leer o modificar por cualquier ruta de paciente (citas, QR y cualquier otra que añada esta spec) un recurso de otro paciente, cambiando el identificador en la petición → se rechaza igual que si no existiera y no se lee ni cambia ningún dato (RS1.b de `docs/security.md`).

## Fuera de alcance
- Firmware del ESP32 y hardware del lector (solo se define lo que el servidor recibe y responde).
- La app Android del paciente (cliente); aquí solo se define lo que el servidor le entrega.
- Funcionamiento sin conexión del lector.
- Consulta del QR por el staff con sus datos de cita, sin efectos (existía en el diseño original como "verificar"; el registro ahora es automático).
- Expiración automática por tiempo (cron o columna `expires_at`); la regla "solo el día de la cita" se evalúa al escanear.
- Envío del QR al paciente por WhatsApp o email.
- Limpieza periódica de imágenes huérfanas (CA11 solo las registra).

## Cobertura de riesgos
Esta spec atiende **parcialmente** RS1 de `docs/security.md` (sus correcciones b y c, traídas desde la
spec 014).

| Corrección | Alcance | Criterios / motivo y destino |
|---|---|---|
| RS1.a Restringir a staff | fuera | Ya la cubre la spec 014 |
| RS1.b Propiedad del recurso | dentro | CA16, CA24 |
| RS1.c Provider del guard `sanctum` | dentro | CA23 |
| RS1.d Tests de acceso denegado | fuera | Los cubre la spec 014; aquí, los casos de abuso CA16, CA21, CA23 y CA24 |

## Seguridad y privacidad
- Datos sensibles involucrados: el QR es una **credencial de acceso** a la cita; contiene solo un identificador firmado, nunca datos personales ni clínicos. El registro de entrada guarda fecha, hora y lector.
- Quién puede hacer qué:
  - Paciente autenticado → consultar el QR de **sus** citas; denegado para citas ajenas. Toda ruta de paciente comprueba que el recurso le pertenece (CA24), y su token no es válido en rutas de staff (CA23).
  - Lector fijo registrado por la clínica → registrar entradas; no puede consultar QR ni datos de citas.
  - Staff → sin acciones nuevas en esta feature (el ciclo de vida del QR lo disparan sus acciones sobre la cita).
  - Sin autenticar → denegado.
- Casos de abuso: CA12–CA24.

## Requisitos no funcionales
- La firma se compara en tiempo constante.
- La clave de firma es dedicada y rotable sin afectar a `APP_KEY`; rotarla invalida los QR emitidos.
- El enlace a la imagen caduca en pocos minutos (valor exacto en `/plan`); la imagen nunca es pública.
- El contenido firmado no debe aparecer en URLs ni en logs de acceso.
- La respuesta al lector llega a tiempo para dar la señal al paciente en recepción (objetivo concreto en `/plan`).

## Preguntas abiertas
- Ninguna.

## Supuestos
- La fecha "de hoy" se evalúa en la zona horaria de la clínica (`config('app.timezone')`).
- El QR se genera dentro del módulo Appointments; no se reutiliza en otros módulos.
- La base es PostgreSQL y las rutas cuelgan de `/api/v1` (el diseño original dice MySQL y `/api`).
- Los controles de acceso de esta feature (CA16, CA17, CA21) se implementan en sus propios endpoints, sin depender de que el objetivo 1 del roadmap (cerrar el control de acceso general) esté terminado.
- El paciente usa su cuenta actual (registro de la spec 002 o alta por el staff) para iniciar sesión en la app Android.
- Las rutas de paciente de esta spec quedan fuera del grupo de rutas de staff de la spec 014.
- CA23 y CA24 cierran RS1.c y RS1.b de `docs/security.md`, que la spec 014 dejó fuera (decisión del usuario, 2026-09-24).

## Notas para /plan
- **Integración nueva de hardware:** el lector ESP32 es un cliente máquina que llama a un endpoint tipo webhook. Requiere ADR (P3/P10) y modelo de amenazas (P11): cómo se registra y autentica cada dispositivo (credencial por dispositivo revocable, firma HMAC de la petición con marca de tiempo contra repetición, TLS), limitación por dispositivo y qué registra el servidor.
- **Alternativa a evaluar:** que la app del paciente dibuje el QR a partir del código firmado, sin guardar imagen en R2. Eliminaría la dependencia de R2, los enlaces temporales y los fallos de borrado (CA1, CA2, CA10, CA11). Si se elige, reescribir esos criterios con `/specify --edit`.
- El disco `s3` tiene `'throw' => false`: un fallo de subida no rompe la transacción, así que CA2 sería solo nominal si no se corrige ([referencias/plan-funcional-aidlc.md](referencias/plan-funcional-aidlc.md), hallazgo 4). Los enlaces temporales requieren `temporaryUrl()` sobre R2.
- Ningún controlador de Appointments usa FormRequest y 11 filtran `$e->getMessage()`: los endpoints nuevos y los flujos modificados (crear, actualizar, borrar cita) deben cumplir P6 y P7.
- Añadir `endroid/qr-code` (u otra librería) requiere ADR (P10).
- La spec 009 (completar cita) se modifica: completar debe expirar el QR (CA7).
- CA23: hoy `config/auth.php` define el guard `api` (driver `sanctum`, provider `users`) y un provider `patients`, pero `auth:sanctum` acepta tokens de `UserModel` y de `PatientModel`. Separar staff y pacientes afecta al login y al logout de pacientes (spec 001) y al middleware `staff` de la spec 014. Revisar ambos en el modelo de amenazas.
- Se implementa **después** de la 014 (decisión del usuario, 2026-09-24) y parte de su código: middleware `staff`, mapa de permisos por rol y patrón de error en los controladores de citas. CA23 cambia el código que recibe un paciente en rutas de staff: hoy es 403 (`EnsureActiveStaff`, spec 014 CA5) y con la autenticación separada será probablemente 401. Declararlo en el plan y actualizar los datasets de acceso de la 014. La 013 también modifica `Create/Update/DeleteAppointmentController` y sus casos de uso, que la 014 ya habrá tocado.
- CA24: generaliza CA16 a toda ruta de paciente. Al cerrarla, marcar RS1.b y RS1.c como mitigadas; RS1 queda mitigado si la 014 ya mitigó RS1.a y RS1.d.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-09-22 | Migrada desde `features/QRModule/` como draft | /init |
| 2026-09-22 | Rediseño de actores: QR en la app del paciente, lector fijo ESP32; vigencia el día de la cita; expiración al completar; imagen privada | /clarify |
| 2026-09-22 | Aprobada por el usuario | Confirmación explícita |
| 2026-09-24 | Sección "Cobertura de riesgos" y citas por ID (RS/OB) | `/init --upgrade` a 1.6.0 (formato 1.5.6); decisión del usuario |
| 2026-09-24 | `extends: [014]` y orden de implementación después de la 014 | Segundo `/analyze 014` (B6); decisión del usuario |
| 2026-09-24 | CA23 (autenticación separada de pacientes y staff) y CA24 (propiedad del recurso en toda ruta de paciente), traídos desde la spec 014 | Riesgo 1 de `docs/security.md`; decisión del usuario |

## Aclaraciones
### Sesión 2026-09-22
- P: ¿Quién puede verificar el QR, registrar la entrada y consultar el QR de una cita? → R: El QR lo tiene el paciente en una app Android; al llegar lo escanea un sistema de registro de entrada; el servidor invalida el QR y elimina su imagen de R2 para no guardar archivos que ya no sirven.
- P: ¿Cuándo es válido registrar la entrada con el QR? → R: Solo el día de la cita.
- P: Si una cita se marca completada sin haber escaneado su QR, ¿qué pasa con el QR? → R: Deja de ser válido (pasa a `expired`).
- P: ¿La imagen del QR es pública o con URL temporal? → R: Temporal o privada. Aclarado: el enlace se genera en cada consulta del paciente, no al crear la cita, así que su caducidad no depende de la fecha de la cita.
- P: ¿Quién opera el lector y cómo se autentica? → R: Un lector fijo sin operador (ESP32) envía un POST a un webhook del servidor, que procesa el acceso y la invalidación de forma automática.
