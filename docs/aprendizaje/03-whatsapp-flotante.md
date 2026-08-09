# 03 — Botón WhatsApp flotante

## ¿Dónde está? (respuesta directa)

- **Componente:** [resources/js/Components/WhatsAppFloat.vue](../../resources/js/Components/WhatsAppFloat.vue)
- **Montado en:** [resources/js/Layouts/PublicLayout.vue](../../resources/js/Layouts/PublicLayout.vue) (importado línea 13, renderizado línea 233)
- **Visible en:** todas las páginas públicas (marketplace, ficha de coche, formulario de solicitud, pricing) porque todas usan `PublicLayout`.

**Posición visual:** esquina **inferior derecha**, fijo (no se mueve al hacer scroll), `z-50` (por encima de todo):

```vue
<a href="https://wa.me/34600000000?text=Hola%20JJ%20Import%20Motors..."
   class="fixed bottom-6 right-6 z-50 ... bg-green-600 rounded-full">
```

## Cómo funciona por dentro (es trivial, y esa es la lección)

No hay JavaScript, no hay estado, no hay API. Es **un `<a>` con un enlace especial**:

```
https://wa.me/34600000000?text=Hola%20me%20interesa%20un%20coche
         ↑ número internacional     ↑ mensaje pre-rellenado (URL-encoded)
         sin "+" ni espacios
```

`wa.me` es el dominio oficial de WhatsApp. Al hacer click:
- En **móvil** → abre la app de WhatsApp directamente con el chat y el mensaje ya escrito.
- En **desktop** → abre WhatsApp Web.

El texto va URL-encoded (`%20` = espacio, `%2C` = coma). En el componente más completo (`ShareCar.vue`) el mensaje se construye dinámicamente con datos del coche:

```js
const text = encodeURIComponent(
  `Hola, estoy interesado en el ${car.brand} ${car.model} (ref. ${car.id})...`
);
const url = `https://wa.me/34675701439?text=${text}`;
```

## Las decisiones de UX (el porqué de cada detalle)

| Detalle | Por qué |
|---|---|
| `fixed bottom-6 right-6` | Esquina inferior derecha = donde el pulgar llega fácil en móvil (zona de confort) |
| `rounded-full h-14 w-14` | FAB (Floating Action Button) — patrón Material Design que todo usuario reconoce |
| `z-50` | Siempre visible, nunca tapado por cards o imágenes |
| `hover:scale-110 hover:-translate-y-1` | Micro-animación que dice "soy clickable" sin texto |
| `aria-label` traducido | Accesibilidad: lectores de pantalla anuncian "Contactar por WhatsApp" |
| `target="_blank" rel="noopener noreferrer"` | Seguridad: `noopener` evita que la página destino acceda a `window.opener` |
| Solo en páginas **públicas** | Un usuario logueado ya tiene canales internos; el botón es para el visitante anónimo |

## Por qué WhatsApp y no un chat propio

1. **El mercado manda:** en compra de coches importados (España/Alemania), el 90% de la conversación comercial YA ocurre en WhatsApp. El cliente no quiere registrarse en tu chat.
2. **Cero infraestructura:** no necesitas WebSockets, ni moderación, ni notificaciones. WhatsApp las tiene todas.
3. **El número queda en el móvil del cliente:** incluso si no compra hoy, tienes canal directo.

## Lo que falta / se podría mejorar

- El número está **hardcoded** (`34600000000` placeholder). Debería venir de la organización (`$org->phone`) para que cada tenant del SaaS tenga SU número.
- En `MarketplaceShow` el mensaje debería incluir el coche concreto (está en `ShareCar.vue`, falta integrarlo en el header por un bug del formatter).

> **Regla reutilizable:** No construyas un canal de comunicación si tu usuario ya vive en otro. Un `<a href="wa.me/...">` vale más que 3 semanas de chat propio. La mejor integración es a veces **un enlace bien puesto**.
