# 13 — PWA: cómo funciona en el móvil

## ¿Qué es una PWA, en una frase?

Una **web que se puede "instalar" como si fuera una app**: icono en la pantalla de inicio del móvil, se abre a pantalla completa (sin barra del navegador), y puede funcionar parcialmente sin conexión. **Sin pasar por App Store ni Google Play.** Es tu misma web, con 2 archivos extra.

## La experiencia del usuario en el móvil (paso a paso)

```
1. El usuario entra a jjimportmotors.com desde Chrome/Safari en el móvil
2. Navega por el marketplace
3. Android: aparece un banner "Añadir a pantalla de inicio"
   iOS: menú Compartir → "Añadir a pantalla de inicio"
4. Acepta → aparece el icono "JJ" en su escritorio, junto a WhatsApp y Fotos
5. Al tocarlo → se abre a PANTALLA COMPLETA
   (sin barra de URL, sin pestañas: parece una app nativa)
6. Con el splash screen del color de marca (estoril) mientras carga
```

## Las 2 piezas técnicas que lo hacen posible

### Pieza 1: `manifest.json` — "el DNI de la app"

[public/manifest.json](../../public/manifest.json). Le dice al sistema operativo CÓMO presentar la web como app:

```json
{
  "name": "JJ Import Motors",
  "short_name": "JJ Import",           // ← texto bajo el icono
  "start_url": "/",                     // ← qué abre al tocar el icono
  "display": "standalone",              // ← SIN barra del navegador
  "theme_color": "#1e3a5f",             // ← barra de estado del móvil (estoril)
  "background_color": "#1e3a5f",        // ← splash screen
  "icons": [
    { "src": "/img/icon-192.png", "sizes": "192x192" },
    { "src": "/img/icon-512.png", "sizes": "512x512" }
  ],
  "shortcuts": [ ... ]                  // ← pulsación larga en el icono: accesos directos
}
```

Los tamaños de icono importan: **192px** es el mínimo para instalabilidad, **512px** para el splash screen de alta resolución. En iOS, además, el `apple-touch-icon.png` (180x180) porque Safari usa el suyo propio.

### Pieza 2: Service Worker (`sw.js`) — "el mayordomo invisible"

[public/sw.js](../../public/sw.js). Un script de JavaScript que el navegador ejecuta **en un hilo separado**, incluso con la pestaña cerrada. Es un proxy entre tu web e internet:

```
App pide /marketplace
        ↓
   Service Worker (intercepta)
        ↓
 ¿Hay red? → sí → pide al servidor, guarda copia en cache, devuelve
 ¿Hay red? → no → devuelve la copia cacheada
        ↓
 Y aparte: escucha eventos PUSH (notificaciones nativas)
```

En nuestro caso hace dos cosas:
1. **Cache:** las páginas visitadas se guardan → segunda visita instantánea, y algo de funcionalidad offline.
2. **Push handler:** cuando llega una notificación push (el canal N6 de notificaciones), el SW la muestra como **notificación nativa del móvil** (la de la barra de arriba, con la pantalla apagada incluso). Al tocarla → abre la app en la URL de la alerta.

### Cómo se registra (en `app.blade.php`)

```js
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js');
}
```

Ese `if` es importante: navegadores antiguos simplemente lo ignoran (la web funciona igual, sin PWA). **Progressive enhancement:** mejora quien puede, no rompe quien no.

## PWA vs App nativa: la comparación honesta

| | PWA (lo que hicimos) | App nativa |
|---|---|---|
| Coste | 2 archivos, 1 día | Meses, 2 plataformas (iOS+Android) |
| Distribución | URL, sin stores | App Store + Google Play (revisiones, comisiones) |
| Actualizaciones | Instantáneas (es tu web) | El usuario debe actualizar |
| Instalación | Voluntaria, fricción media | El usuario debe buscarla e instalarla |
| Notificaciones push | ✅ (Android) / ⚠️ limitado (iOS) | ✅ total |
| Acceso hardware | Limitado | Total |

**Por qué PWA aquí:** para un SaaS B2B de gestión de coches, el usuario quiere "abrir el panel rápido desde el móvil", no una experiencia nativa con cámara/GPS/sensores. La PWA da el 80% del valor (icono, fullscreen, push, cache) por el 5% del coste.

**Matiz importante del plan original:** en el documento de anti-overengineering, PWA estaba descartado como "no core". Se revirtió la decisión en Sprint 7 porque el coste real resultó ser mínimo (manifest + iconos + registro inline) y el beneficio en engagement móvil es real. Lección: las decisiones de "no hacer" también se revisan con datos de esfuerzo real.

## Limitaciones conocidas (para que no te sorprendan)

- **iOS es restrictivo:** Safari limita los push (hasta iOS 16.4 no existían; aún requiere que el usuario instale la PWA primero). En Android todo funciona mejor.
- **No apareces en las stores:** si tu marketing depende de "descarga nuestra app en Google Play", la PWA no sirve.
- **El usuario debe instalarla manualmente** en iOS (no hay banner automático como en Android).

## Cómo probarlo tú mismo

1. Despliega en HTTPS (obligatorio: los SW solo funcionan en HTTPS o localhost).
2. Abre la web en Chrome Android → menú ⋮ → "Instalar app" / "Añadir a pantalla de inicio".
3. DevTools → pestaña **Application** → Manifest (ves cómo la lee el navegador) y Service Workers (ves el SW activo).
4. Lighthouse → auditoría PWA → te da el checklist de qué falta.

Tests automatizados: `tests/Feature/PWATest.php` (4 tests: manifest válido, iconos existen, SW accesible...).

> **Regla reutilizable:** PWA = `manifest.json` (identidad) + `sw.js` (cache/push) + HTTPS (requisito). Es la forma más barata de "tener app" cuando tu producto es 90% contenido/formularios y 10% hardware. Monta el manifest desde el día 1 de cualquier web pública; el service worker añádelo cuando tengas algo que cachear o notificar.
