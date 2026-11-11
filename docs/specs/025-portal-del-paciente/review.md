---
spec: 025-portal-del-paciente
verdict: approved
round: 2
date: 2026-11-11
base: 999a059
head: 4295666
human_signoff: pending
---

# Review · 025 Portal del paciente

## Resumen
Ronda 2 (`--rerun`) sobre `999a059..4295666`: las cinco correcciones pedidas en la ronda 1
([history/review.r1.md](history/review.r1.md)) están hechas, con su comprobación rompiendo lo
protegido, y la verificación automática completa pasa sin fallos. Sin hallazgos nuevos. Los menores
R6–R32 siguen aceptados como deuda. Revisado por el agente sin subagentes (diff de 9 KB),
releyendo los archivos del rango.
Conteo: bloqueante 0 · importante 0 · menor 0 (nuevos)

## Verificación automática
| Comando | Resultado |
|---|---|
| `./vendor/bin/pest --parallel` | ✅ 2184 pasan, 0 fallos (línea base sin aparecer esta vez) |
| `vendor/bin/pint --test` sobre los 65 archivos PHP de la spec | ✅ |
| `npm run build` | ✅ |
| `npm run test:ui -- --only portal` y `--only detalle-portal` (dos veces cada una, tras T036 y T037) | ✅ 18 + 12 visitas sin fallos |
| `python .ai/bin/aidd.py validate` | ✅ 0 errores |

## Criterios de aceptación
Sin cambios respecto a la ronda 1, salvo los ⚠ que se cierran:

| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `PortalAppointmentsTest` (caso nuevo a las 23:30 de la clínica) | ✅ | R3: falla con "hoy" en UTC |
| CA5 | ui-audit `portal-detalle-completada` (textos sin posición, instrucciones y recomendaciones) | ✅ | R5 |
| CA9 | ui-audit `portal-mis-citas-fallo` (flujo "Reintentar falla otra vez") | ✅ | R4: falló antes del cambio |
| CA19 | `PortalUnexpectedErrorTest` (`->once()` en cada doble) | ✅ | R2: con un método mal nombrado, ahora falla |
| CA2–CA4, CA6–CA8, CA10–CA18, CA20 | los de la ronda 1 | ✅ | |

## Conformidad con el plan
- Cambios fuera del plan: ninguno; los cinco archivos del rango los citan T033–T037.
- Partes del plan sin implementar: ninguna.

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1–P13, P15 | ✅ | Sin cambios de código de producción salvo `portal/appointments.js` (foco) |
| P14 | ❌ aceptado | Aprobado por el usuario el 2026-11-10 |
| Lint (Pint) | ✅ | R1 corregido |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks (`999a059..HEAD`) | ✅ 0 hallazgos |
| semgrep | ➖ el rango no toca `app`, `config`, `bootstrap` ni `routes`; 0 hallazgos en la ronda 1 |
| composer audit / npm audit | sin cambios de dependencias; 8 avisos de Symfony bajo EX2 (vence 2026-11-12) |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| A01–A10 (ronda 1) | ✅ / ➖ | Sin cambios; el JS nuevo solo mueve el foco |

## Observabilidad
- Sin cambios: P14 ❌ aceptado, sin logs nuevos.

## SEO
| Tema | Resultado | Evidencia |
|---|---|---|
| 2, 3 | ✅ | Sin cambios respecto a la ronda 1 |

## Hallazgos
Seguimiento de la ronda 1:

| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| R1 | bloqueante | corregido (T033, `441b245`) | `pint --test` limpio |
| R2 | importante | corregido (T034, `a2a6155`) | Con un doble mal nombrado: antes 6 pasan, ahora 2 fallan con `InvalidCountException` |
| R3 | importante | corregido (T035, `986845e`) | Con `now()->utc()` el caso nuevo falla |
| R4 | importante | corregido (T036, `6fde699`) | El flujo nuevo falló con el código anterior y pasa con el nuevo |
| R5 | importante | corregido (T037, `c6374fb`) | Sin el texto de las recomendaciones fallan dos entradas |
| R6–R32 | menor | aceptados como deuda (ronda 1) | → roadmap en `/release` |

Hallazgos nuevos: ninguno.

## Preparación para release
- Rollback factible: sí, `docker/prod/rollback.sh`; sin migraciones ni datos que revertir.
- Migraciones compatibles con la versión anterior: no hay migraciones.
- Docs actualizadas: sí (T051, T090, T091).
- SCA: EX2 vence el 2026-11-12; el `/chore` de Symfony 7.4.23 debe ir antes de liberar.
- Cobertura de riesgos: RS1.b y RS1.c en curso (spec 025); pasan a mitigadas en `/release`.

## Tareas añadidas
- T033 ← R1
- T034 ← R2
- T035 ← R3
- T036 ← R4
- T037 ← R5

## Aceptados sin tarea
Aceptados por el usuario el 2026-11-11 como deuda (ronda 1):
- **R6** → roadmap: la próxima cita del portal filtra todo el historial en PHP en lugar de una consulta con `LIMIT 1`
- **R7** → roadmap: el detalle del portal vuelve a pedir la lista completa en cada apertura
- **R8** → roadmap: `auth:sanctum,patient` en el grupo del portal hace una búsqueda de token de más
- **R9** → roadmap: la regla "cita abierta" repetida en varios casos de uso, recursos y JS
- **R10** → roadmap: el historial se ordena en PHP y otra vez en el navegador
- **R11** → roadmap: utilidades de escape y de fechas duplicadas en el JS del portal
- **R12** → roadmap: `portal-inicio` no comprueba tratamiento y doctor de la tarjeta
- **R13** → roadmap: comprobación de cuenta inactiva repetida entre staff y paciente
- **R14** → roadmap: el saludo del paciente sin el respaldo "Usuario"
- **R15** → roadmap: comentarios en línea en controladores, recursos y rutas del portal
- **R16** → roadmap: dos sentencias en una línea en `portal/home.js`
- **R17** → roadmap: el 409 y el 400 de los controladores del portal capturan clases amplias
- **R18** → roadmap: `GuestRedirectTest` filtra por el middleware exacto
- **R19** → roadmap: `PortalPagesTest` no prueba el `noindex` fuera del `/dashboard` del staff
- **R20** → roadmap: `withPatientToken()` sin uso
- **R21** → roadmap: `GetPatientPrescriptionsUseCase` sin comprobación de arquitectura
- **R22** → roadmap: dobles de autorización duplicados en los tests unitarios
- **R23** → roadmap: aserciones de `PortalPerformanceTest` en el caso equivocado
- **R24** → roadmap: `PortalPrescriptionsTest` sin `reprogramada`
- **R25** → roadmap: el detalle del portal sin `aria-busy` mientras carga
- **R26** → roadmap: el error del detalle se ve dos veces
- **R27** → roadmap: "Recetas" vacía mientras carga
- **R28** → roadmap: landmark repetido en Mis citas
- **R29** → roadmap: "Ver" sin la hora y tabla del historial sin `scope` ni nombre
- **R30** → roadmap: `LogoutController` registra el mensaje de la excepción (fuera del diff, `/fix`)
- **R31** → roadmap: clave del limitador `api` sin el tipo de cuenta
- **R32** → roadmap: `viewAttribute` sin escapar en el historial compartido
