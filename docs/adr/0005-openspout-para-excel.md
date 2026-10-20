---
id: 0005
status: accepted
date: 2026-10-19
---

# ADR 0005 · OpenSpout para generar archivos de Excel

## Contexto
La spec 019 pide descargar el reporte mensual como un archivo de Excel real, con varias hojas,
encabezados con formato, primera fila fija y porcentajes, que se abra sin avisos de reparación
(CA8). Los nombres de tratamientos y del staff no pueden ejecutarse como fórmulas (CA13). El
proyecto no tiene ninguna librería para escribir `.xlsx` y P10 exige un ADR para toda dependencia
nueva.

## Decisión
Añadir `openspout/openspout` en la versión fijada **5.12.0** (sin rango) como dependencia de
producción en `composer.json`.

| Paquete | Versión | Fecha de esa versión | Licencia | Repositorio | Scripts de instalación | Dependencias |
|---|---|---|---|---|---|---|
| `openspout/openspout` | 5.12.0 | 2026-09-24 (25 días el 2026-10-19; mínimo 7) | MIT | https://github.com/openspout/openspout | ninguno | ninguna de Composer; extensiones `dom`, `filter`, `libxml`, `xmlreader`, `zip`, ya presentes en la imagen (`docker/Dockerfile` instala `zip`; el resto viene con PHP 8.4) |

Verificación del 2026-10-19 (`shared/agent-security.md` §2): el nombre existe en Packagist con
ese nombre exacto; ~95 millones de descargas en total y ~8,9 millones al mes; repositorio activo,
con versiones publicadas cada pocas semanas; exige PHP `~8.4.0 || ~8.5.0 || ~8.6.0`, compatible con
el proyecto. Las versiones 5.12.1 (2026-10-12) y 5.12.2 (2026-10-13) tienen menos de 7 días y no se
usan.

Prueba de concepto del 2026-10-19, en `/tmp` del contenedor `app` y fuera del repositorio:
- Genera dos hojas con nombre propio, encabezados en negrita sobre `#FBE9EE`, anchos de columna,
  la primera fila de datos fija (`withFreezeRow`) y formato `0.0%`.
- Un `StringCell` con `=SUM(A1)+1` o `@cmd` se guarda como texto en línea (`t="inlineStr"`), sin
  fórmula.
- `Row::fromValues()` y `Cell::fromValue()` sí convierten un texto que empieza por `=` en
  fórmula (`<f>1+1</f>`). Regla: el código del reporte **nunca** usa esas fábricas para textos;
  siempre crea `StringCell` o `NumericCell` de forma explícita, y un test lo comprueba leyendo el
  archivo.

## Alternativas consideradas
- **PhpSpreadsheet 5.10.0** (2026-09-17, MIT, ~358 M descargas): más completa (fórmulas,
  gráficas, lectura), pero arrastra cinco paquetes más (`maennchen/zipstream-php`,
  `markbaker/complex`, `markbaker/matrix`, `composer/pcre`, `psr/simple-cache`) y la extensión
  `gd`, y usa más memoria. El reporte no necesita nada de lo que añade.
- **CSV sin dependencia:** descartado por el usuario en la spec 019 (sin hojas ni formato, y el
  mismo riesgo de fórmulas).
- **Escribir el `.xlsx` a mano** (ZIP con XML): sin dependencia, pero hay que mantener el formato
  Office Open XML y sus estilos; más riesgo de archivos que Excel "repara".

## Consecuencias
- Positivas: una dependencia sin transitivas y que escribe en streaming, con poca memoria. Su
  lector XLSX permite que los tests abran el archivo generado sin otra librería.
- Negativas: una dependencia más que vigilar con `composer audit`; las fábricas que deducen el
  tipo de celda son peligrosas para textos y quedan prohibidas en este código.
- Para subir de versión: revisar el CHANGELOG (en especial cambios en la detección de fórmulas)
  y repetir el test de CA13.
