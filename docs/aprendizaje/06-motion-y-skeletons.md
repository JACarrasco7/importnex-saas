# 06 — @vueuse/motion + Skeletons: dónde verlos

## Skeletons (carga percibida)

### Dónde están

- **Componentes:** [resources/js/Components/Skeleton.vue](../../resources/js/Components/Skeleton.vue) y [resources/js/Components/SkeletonCard.vue](../../resources/js/Components/SkeletonCard.vue)
- **En acción real:** entra a `/cars` o `/clients` con la conexión lenta (en DevTools → Network → "Slow 3G"). Verás el skeleton antes de que lleguen los datos.
- **Combinados con deferred props:** en `Cars/Index.vue` y `Clients/Index.vue` se usa `<WhenVisible>` de Inertia con el skeleton como `#fallback`.

### ¿Qué es un skeleton y por qué se usa?

**Antes:** spinner centrado → el usuario ve una página vacía girando → *"esto va lento"*.

**Ahora:** silueta gris animada con la FORMA del contenido final:

```
┌─────────────────────────┐
│ ██████░░░░  (shimmer)   │   ← "card" gris pulsando
│ ████░░                  │
│ ████████░░              │
└─────────────────────────┘
```

El efecto `shimmer` es un gradiente CSS que se mueve (`animate-pulse` o keyframes custom). El cerebro del usuario interpreta "ya está cargando la estructura" → la espera **se siente más corta** aunque dure lo mismo.

**La regla de los 300ms:** si la carga dura <300ms, no pongas nada (el skeleton parpadea y molesta). Si dura más, skeleton > spinner. Por eso solo están en listados pesados (Cars, Clients), no en toda la app.

### Con Inertia v2 deferred props

```php
// Controller: la prop "cars" NO viaja en el primer response
return Inertia::render('Cars/Index', [
    'cars' => Inertia::defer(fn () => Car::paginate(15)),
]);
```

```vue
<!-- Vue: mientras no llega, muestra skeleton -->
<WhenVisible data="cars">
    <template #fallback><SkeletonCard v-for="i in 6" :key="i" /></template>
    <CarCard v-for="car in cars.data" :key="car.id" :car="car" />
</WhenVisible>
```

La página pinta el layout al instante; los coches llegan en una segunda petición automática.

## @vueuse/motion (micro-animaciones)

### Dónde verlo

- **Página:** [resources/js/Pages/Welcome.vue](../../resources/js/Pages/Welcome.vue) (la landing `/`)
- **Instalación:** commit `363ae25` — `npm i @vueuse/motion`

### Qué hace

Directiva `v-motion` que anima elementos al montarse o al hacer scroll:

```vue
<h1 v-motion
    :initial="{ opacity: 0, y: 40 }"
    :enter="{ opacity: 1, y: 0, transition: { duration: 600 } }">
    Importa tu próximo coche con confianza
</h1>
```

→ El título "sube" desde abajo apareciendo suavemente. Las cards de features hacen lo mismo con delay escalonado (efecto "cascada").

### ¿Por qué @vueuse/motion y no otra cosa?

| Alternativa | Por qué no |
|---|---|
| Animaciones CSS a mano | Funcionan, pero el "animate on scroll" (IntersectionObserver manual) es código repetitivo y frágil |
| GSAP | Potentísima pero ~70KB y overkill para fades/slides |
| Transiciones de Vue (`<Transition>`) | Solo animan al entrar/salir del DOM, no al hacer scroll |

`@vueuse/motion` = ~10KB, API declarativa, y usa la Web Animations API nativa (rendimiento GPU).

### La decisión honesta

En el plan estaba "migrar a @vueuse/motion para microinteracciones" en toda la app (Sprint 3.3). **Solo se aplicó en Welcome.vue.** ¿Por qué? Porque animar todo tiene coste (rendimiento en móviles antiguos + mareo si se abusa). Se aplicó donde más impacta: la landing pública, que es la primera impresión.

> **Regla reutilizable:** Skeletons donde la espera >300ms (listados, dashboards). Animaciones donde quieres emoción (landing, onboarding, éxito de una acción). Nunca en formularios ni en la 50ª pantalla de un CRUD: ahí la velocidad percibida manda, no el adorno.
