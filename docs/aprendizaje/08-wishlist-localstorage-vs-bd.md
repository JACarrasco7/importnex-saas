# 08 — Wishlist: ¿por qué localStorage y NO base de datos?

## La pregunta correcta

Guardar la wishlist en la BD parece lo "serio": tabla `wishlists`, relación con el coche, migración... ¿Por qué se eligió `localStorage`?

## El contexto que lo decide todo

**La wishlist es para visitantes ANÓNIMOS del marketplace.** No están logueados. No hay `user_id` que poner en la tabla.

Opciones reales:

| Opción | Problema |
|---|---|
| Tabla con `user_id` | ❌ El visitante no tiene cuenta. Obligarle a registrarse para guardar un favorito = abandono inmediato |
| Tabla con `session_id` / cookie | ❌ Funciona, pero es complejidad de backend (migración, limpieza de sesiones expiradas, GDPR) para un dato que no te importa perder |
| **localStorage** | ✅ Cero backend, cero migración, funciona offline, instantáneo |

## Cómo está implementado

[resources/js/Composables/useWishlist.js](../../resources/js/Composables/useWishlist.js):

```js
const STORAGE_KEY = 'importnex_wishlist';
const wishlist = ref([]);

// Al arrancar: leer localStorage
// add/remove/toggle/list/clear → mutan el ref y persisten
```

Y el botón [WishlistButton.vue](../../resources/js/Components/WishlistButton.vue) (corazón en cada card del marketplace).

Como es un **composable con estado compartido** (el `ref` está fuera de la función), todas las páginas ven la misma wishlist reactiva sin Pinia ni servidor.

## Cuándo localStorage vs cuándo BD (la tabla de decisión)

| Pregunta | localStorage | Base de datos |
|---|---|---|
| ¿El usuario está autenticado? | No | Sí |
| ¿El dato lo necesita OTRO dispositivo? | No (solo este navegador) | Sí (móvil + PC) |
| ¿El negocio necesita ANALIZAR el dato? | No | Sí ("¿qué coches se guardan más?") |
| ¿Perder el dato sería grave? | No (es una lista de deseos) | Sí (pedidos, pagos) |
| ¿Hay que cumplir GDPR con el dato? | No (nunca sale del navegador) | Sí |

La wishlist anónima responde: no, no, no-crítico, no, no → **localStorage gana 5-0**.

## La desventaja aceptada (y su plan de escape)

**Lo que pierdes:** si el usuario cambia de navegador/dispositivo, su wishlist no viaja con él. Y tú no puedes hacer analytics de "coches más guardados".

**El patrón de migración (para cuando importe):**

```
Visitante anónimo guarda 3 coches (localStorage)
        ↓
Semanas después se registra / envía solicitud
        ↓
En ese momento: syncWishlistToServer(localStorage) → BD
        ↓
A partir de ahí, wishlist persistente multi-dispositivo
```

Este patrón ("local first, sync on auth") lo usan carritos de Amazon, wishlists de Zalando, etc. **Empiezas gratis y migras cuando el dato demuestra valor.**

## Detalles de implementación que importan

1. **Coche vendido:** si un coche guardado se vende, NO se borra de la wishlist — se muestra tachado con badge "No disponible". Borrarlo sería hostil ("¿dónde está mi coche?").
2. **Conversión:** el botón "Enviar solicitud con estos coches" pre-rellena el formulario: *"Estoy interesado en: BMW Serie 3 (ref 12), Audi A4 (ref 14)"*. La wishlist no es un capricho, es un **funnel de conversión**.
3. **Sin login = sin fricción:** cada campo de registro que pides antes de dar valor reduce la conversión ~10-20%.

> **Regla reutilizable:** No preguntes "¿dónde guardo esto?" sino "¿quién necesita este dato y cuándo?". Si solo lo necesita ESTE usuario en ESTE navegador y perderlo no duele → localStorage. Añade BD cuando el dato cruce fronteras (dispositivos, equipos, analytics). La mejor arquitectura es la que no construyes hasta que la necesitas.
