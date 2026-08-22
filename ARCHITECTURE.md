# Arquitectura de DentissaApp

Documento técnico generado a partir de un análisis estático del código (Laravel 12 / PHP 8.2). Describe el patrón arquitectónico, el modelo de datos y el flujo del caso de uso principal.

## 1. Resumen técnico

**Stack tecnológico**

| Capa | Tecnología |
|---|---|
| Backend | Laravel 12, PHP ^8.2 |
| Autenticación | Laravel Sanctum 4 (modo SPA/cookie, sin Policies — autorización custom) |
| Base de datos | PostgreSQL (`config/database.php`, `DB_CONNECTION` por defecto `pgsql`) |
| Cola / Cache | Predis (Redis) |
| Integraciones externas | Twilio (WhatsApp), Brevo (email transaccional) |
| Frontend | Blade + JS vanilla por página ("pages" pattern), Axios/fetch, Vite 7, Tailwind CSS 4 |

**Patrón arquitectónico**: **Monolito Modular** con capas internas de **Arquitectura Hexagonal / Clean Architecture** (`Domain / Application / Infrastructure`) por módulo. No existe un `app/Http/Controllers` central: cada módulo bajo `app/Modules/*` es autocontenido, con sus propias entidades de dominio, value objects, casos de uso, interfaces de repositorio, implementaciones Eloquent y migraciones. Un *shared kernel* en `app/Core` centraliza autorización (`CurrentActorAuthorizationService`), un value object base para IDs (`UuidIdentifier`) y middlewares transversales.

Módulos identificados: `Auth`, `Users`, `Patients`, `Appointments`, `AppointmentTracking` (seguimiento clínico/recetas, el más reciente), `ContentManagement` (con submódulos `Certificaciones`, `Galeria`, `Promociones`, `Testimonios`), `Estadisticas`, `Email`, `whatsApp`.

**Flujo de datos general**

```
Browser (Blade + JS por página)
   → routes/web.php (vistas)  |  routes/api.php (JSON, prefijo /v1)
   → Middleware (throttle:api → sanctum.cookie → auth:sanctum → only.admin)
   → Controller de un solo método (Infrastructure/Http/Controllers)
   → UseCase (Application) — orquesta reglas de negocio
   → Entidad / Value Objects (Domain) — validan invariantes
   → Repository Interface (Domain) → Eloquent Repository (Infrastructure)
   → PostgreSQL
   → (efectos secundarios) Evento de dominio → Listener en cola → Twilio / Brevo
```

**Puntos de entrada clave (Controllers/Endpoints)**

- `CreateAppointmentController`, `CompleteAppointmentController` — ciclo de vida de citas.
- `UpdateAppointmentTrackingController`, controladores de `Prescription*` — seguimiento clínico y recetas.
- `GetPatientByIdController` y CRUD de `Patients` (direcciones, contacto, datos médicos, expediente).
- CRUD de `Users`, `Treatments`.
- CRUD por submódulo de `ContentManagement` (Certificaciones, Galería, Promociones, Testimonios) — públicos en lectura, `only.admin` en escritura.
- `Auth`: login, register, logout, reset de contraseña.

**Autorización**: no usa Laravel Policies. `App\Core\Authorization\CurrentActorAuthorizationService::assertCan()` valida que el usuario autenticado esté activo y, para permisos administrativos, que su rol sea `administrador` contra una lista blanca hardcodeada. Las rutas de agenda/citas solo requieren `auth:sanctum` (cualquier staff autenticado), no `only.admin`.

---

## 2. Diagrama de Arquitectura / Visión General del Sistema

```mermaid
graph TD
    subgraph Client["Cliente"]
        Browser["Browser<br/>Blade views + JS vanilla por página<br/>(agenda, patients, records, tratamientos...)"]
    end

    subgraph Laravel["Aplicación Laravel (Monolito Modular)"]
        Routes["routes/web.php · routes/api.php (v1)"]

        subgraph MW["Middleware pipeline"]
            direction LR
            M1["throttle:api"] --> M2["sanctum.cookie<br/>(InjectSanctumTokenFromCookie)"]
            M2 --> M3["auth:sanctum"]
            M3 --> M4["only.admin<br/>(rutas admin)"]
        end

        subgraph Core["app/Core (shared kernel)"]
            Auth["CurrentActorAuthorizationService"]
            Uuid["UuidIdentifier (Domain VO base)"]
        end

        subgraph Modules["app/Modules/*"]
            direction TB
            ModAuth["Auth"]
            ModUsers["Users"]
            ModPatients["Patients"]
            ModAppt["Appointments"]
            ModTracking["AppointmentTracking<br/>(seguimiento + recetas)"]
            ModCMS["ContentManagement<br/>(Certificaciones, Galeria,<br/>Promociones, Testimonios)"]
            ModStats["Estadisticas"]
            ModEmail["Email"]
            ModWA["whatsApp"]
        end

        subgraph Layers["Capas internas por módulo (ej. Appointments)"]
            Ctrl["Infrastructure/Http/Controllers<br/>(un método por acción)"]
            UC["Application/UseCases"]
            Dom["Domain/Entities + ValueObjects<br/>+ RepositoryInterface"]
            Repo["Infrastructure/Persistence/Eloquent<br/>(Repository + Model)"]
            Ctrl --> UC --> Dom --> Repo
        end
    end

    DB[("PostgreSQL")]

    subgraph External["Servicios externos"]
        Twilio["Twilio API<br/>(WhatsApp)"]
        Brevo["Brevo<br/>(Email)"]
    end

    Browser -->|"fetch/axios (XSRF + cookie)"| Routes
    Routes --> MW
    MW --> Auth
    MW --> Modules
    ModAppt --> Layers
    Repo --> DB
    ModWA -->|"confirmaciones de cita"| Twilio
    ModEmail --> Brevo
    ModAppt -.->|"evento de dominio"| ModWA
    Modules -.-> Core
```

---

## 3. Diagrama Entidad-Relación de la Base de Datos

Núcleo clínico/operativo (sin tablas pivote — todas las relaciones son 1:N, con una 1:1 entre `appointments` y `appointment_tracking`):

```mermaid
erDiagram
    ROLES ||--o{ USERS : "asigna"
    USERS ||--o{ APPOINTMENTS : "atiende (user_id)"
    USERS ||--o{ PERSONAL_ACCESS_TOKENS : "posee (tokenable)"

    PATIENTS ||--o{ ADDRESSES : "tiene"
    PATIENTS ||--o{ CONTACT_INFO : "tiene"
    PATIENTS ||--o{ MEDICAL_DATA : "tiene"
    PATIENTS ||--o{ APPOINTMENTS : "agenda (patient_id)"
    PATIENTS ||--o{ PERSONAL_ACCESS_TOKENS : "posee (tokenable)"

    TREATMENTS ||--o{ APPOINTMENTS : "es tipo de"

    APPOINTMENTS ||--o| APPOINTMENT_TRACKING : "genera seguimiento"
    APPOINTMENT_TRACKING ||--o{ APPOINTMENT_TRACKING_PRESCRIPTIONS : "prescribe"

    ROLES {
        uuid id PK
        string name UK
        string description
    }
    USERS {
        uuid id PK
        string first_name
        string last_name
        string email UK
        string password
        enum status "active/inactive"
        uuid role_id FK
    }
    PATIENTS {
        uuid id PK
        string first_name
        string last_name
        string email UK
        string password
        string status
        enum role "patient"
    }
    ADDRESSES {
        bigint id PK
        uuid patient_id FK
        string street
        string city
        string state
        string postal_code
    }
    CONTACT_INFO {
        bigint id PK
        uuid patient_id FK
        string phone_number
        string emergency_contact
        string email
    }
    MEDICAL_DATA {
        bigint id PK
        uuid patient_id FK
        string blood_type
        json allergies
        json medications
        json last_dentist_visit
    }
    TREATMENTS {
        bigint id PK
        string name
        string description
        int time "minutos, nullable"
    }
    APPOINTMENTS {
        uuid id PK
        date date
        time time
        bool whatsapp_reminder
        enum status "asignada/completada/cancelada/reprogramada"
        bigint treatment_id FK
        uuid user_id FK
        uuid patient_id FK
    }
    APPOINTMENT_TRACKING {
        uuid id PK
        string reason
        json symptoms
        string diagnosis
        string procedure_performed
        string observations
        string recommendations
        uuid appointment_id FK "UK - relación 1:1"
    }
    APPOINTMENT_TRACKING_PRESCRIPTIONS {
        uuid id PK
        string medication
        string dosage
        uint duration_days
        uint daily_frequency
        string instructions
        uuid appointment_tracking_id FK
    }
    PERSONAL_ACCESS_TOKENS {
        bigint id PK
        string tokenable_type "polymorphic"
        string tokenable_id "polymorphic (uuid users/patients)"
        string name
        string token UK
        string abilities
        timestamp expires_at
    }
```

> Nota: el módulo `ContentManagement` mantiene tablas independientes (`certifications`, `galery_images`, `promotions`, `testimonials`) para contenido público del sitio (certificaciones, galería, promociones, testimonios). No tienen relación con el dominio clínico y se omiten del ERD para mantenerlo legible.

---

## 4. Diagrama de Secuencia — Caso de uso principal: Crear cita con confirmación por WhatsApp

Flujo real trazado desde `resources/js/pages/agenda/create-appointment.js` hasta la persistencia y el envío de WhatsApp vía Twilio.

```mermaid
sequenceDiagram
    actor U as Usuario (staff)
    participant JS as Browser<br/>create-appointment.js
    participant MW as Middleware<br/>(throttle · sanctum.cookie · auth:sanctum)
    participant C as CreateAppointmentController
    participant UC1 as RetriveDataForScheduledAppointmenEventUseCase
    participant UC2 as CreateAppointmentUseCase
    participant Svc as AppointmentsService
    participant Repo as EloquentAppointmentRepository
    participant DB as PostgreSQL
    participant Ev as Evento: ScheduledAppointment
    participant L as CreatedAppointmentListener (queued)
    participant UC3 as SendAppointmentConfirmationUseCase
    participant Tw as TwilioConection
    participant API as Twilio API

    U->>JS: completa formulario de cita
    JS->>MW: POST /api/v1/appointments<br/>(fetch, credentials:include, X-XSRF-TOKEN)
    MW->>MW: valida throttle, inyecta token Sanctum,<br/>autentica auth:sanctum
    MW->>C: __invoke(Request)
    C->>C: construye CreateAppointmentDTO
    C->>UC1: execute(dto)
    UC1->>UC2: execute(dto)
    UC2->>Svc: findByStatusAndDate(date)
    Svc-->>UC2: citas existentes ese día
    UC2->>UC2: validateScheduleAvailability()<br/>(detecta solapamiento de horario)
    alt Conflicto de horario
        UC2--xC: AppointmentScheduleConflictException
        C-->>JS: 409 Conflict (JSON)
    else Horario disponible
        UC2->>UC2: construye AppointmentEntity<br/>(AppointmentDate, AppointmentTime, TreatmentId, UserId, PatientId)
        UC2->>Svc: saveAppointment(entity)
        Svc->>Repo: save(entity)
        Repo->>DB: INSERT/UPDATE appointments
        DB-->>Repo: OK
        UC1->>UC1: carga datos de paciente + contacto<br/>(PatientsRepository, ContactInfoRepository)
        UC1-->>C: ScheduledAppointment (evento con phone/name/date/time)
        C->>Ev: event(scheduledAppointmentEvent)
        C-->>JS: 201 Created {"message": "Appointment created successfully"}
        Ev-->>L: handle() (async, cola)
        L->>L: construye SendConfirmationAppointmentMessageDTO
        L->>UC3: execute(dto)
        UC3->>Tw: sendTemplate(whatsapp:{phone}, variables)
        Tw->>API: llamada API Twilio
        API-->>Tw: confirmación de envío
    end
```

**Notas del flujo**

- No existen `FormRequest` ni `Policy` classes en el proyecto: la validación ocurre en los Value Objects de dominio (lanzan `ValueObjectsException`) y la autorización pasa por `CurrentActorAuthorizationService`.
- El envío de WhatsApp es **asíncrono** (`CreatedAppointmentListener implements ShouldQueue`, 3 intentos, backoff 15s) y no bloquea la respuesta HTTP al usuario.
- Si el paciente no tiene teléfono registrado, el listener omite el envío sin fallar la creación de la cita.
