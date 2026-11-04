---
id: 0007
status: accepted
date: 2026-11-04
---

# ADR 0007 · chillerlan/php-qrcode para generar el QR de las citas

## Contexto
La spec 024 pide que Core genere el QR de cada cita, con solo su UUID, como imagen SVG
(`GET /api/v1/appointments/{id}/qr`). Los tests tienen que probar que el contenido **decodificado**
es exactamente el id (CA1). El proyecto no tiene ninguna librería de QR y P10 exige un ADR para
toda dependencia nueva.

## Decisión
Añadir `chillerlan/php-qrcode` en la versión fijada **6.0.1** (sin rango) como dependencia de
producción en `composer.json`.

| Paquete | Versión | Fecha de esa versión | Licencia | Repositorio | Scripts de instalación | Dependencias |
|---|---|---|---|---|---|---|
| `chillerlan/php-qrcode` | 6.0.1 | 2026-03-18 (231 días el 2026-11-04; mínimo 7) | MIT o Apache-2.0 | https://github.com/chillerlan/php-qrcode | ninguno | `chillerlan/php-settings-container` (resuelve 3.3.0, 2026-03-20, MIT, sin dependencias de Composer); extensión `mbstring`, ya presente en la imagen. Para **leer** QR usa `gd`, también presente (`docker/Dockerfile`) |

Verificación del 2026-11-04 (`shared/agent-security.md` §2): el nombre existe en Packagist con ese
nombre exacto; ~57 millones de descargas en total y ~7,4 millones al mes; repositorio activo; exige
PHP `^8.2`, compatible con el proyecto. Composer resuelve `php-settings-container` 3.3.0 y no 4.0.0,
porque 6.0.1 pide `^3.2.1`.

Prueba de concepto del 2026-11-04, en `/tmp` del contenedor `app` y fuera del repositorio:
- `QRMarkupSVG` con `outputBase64 = false` y corrección de error M genera un SVG de unos 25 kB
  en ~14 ms para un UUID; sin `<script>` ni referencias externas.
- `QRGdImagePNG` + `readFromBlob()` devuelve el UUID exacto.
- `readFromBlob()` no lee SVG (GD no lo entiende). Un ayudante de test rasteriza con GD los módulos
  oscuros del SVG (`<path class="… dark …" d="Mx y h1 v1 h-1Z …">`, con `connectPaths = false`) y
  la librería lo decodifica al UUID exacto. Así CA1 se prueba sobre la respuesta real del endpoint.

## Alternativas consideradas
- **`bacon/bacon-qr-code` 3.1.1** (2026-04-05, BSD-2): genera SVG, pero no lee QR. Los tests de CA1
  necesitarían otra librería solo para decodificar. Arrastra `dasprid/enum` y pide `ext-iconv`.
- **`endroid/qr-code` 6.2.0** (2026-11-03): tiene menos de 7 días, exige PHP `^8.5` y se apoya en
  `bacon/bacon-qr-code`. Descartada.
- **Generar el QR en el navegador** con una librería npm: el endpoint que pide el microservicio
  desaparece y la prueba de CA1 pasa al navegador. Además añade una dependencia npm que también
  necesita ADR.

## Consecuencias
- Positivas: una sola dependencia de producción, con una transitiva pequeña. El lector incluido
  permite probar el contenido sin dependencias de desarrollo nuevas. No hace falta guardar nada:
  el QR se genera en cada petición.
- Negativas: una dependencia más que vigilar con `composer audit`. El ayudante de test depende del
  formato de los `path` del SVG; si una versión nueva lo cambia, ese test falla y hay que ajustarlo.
- Para subir de versión: revisar el CHANGELOG (opciones de `QROptions` y formato de `QRMarkupSVG`)
  y repetir los tests de CA1.
