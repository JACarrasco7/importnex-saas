# Guía de uso — Sistema JJ Import Motors (Claude + Laravel)

> Esta guía explica cómo trabajar cada día con las dos piezas del sistema: **Claude** (que
> investiga, valora y prepara los documentos) y la **web de Laravel** (donde vive de verdad el
> inventario y los clientes). No es para clientes — es la guía interna de cómo tú usas la
> herramienta.

**Idea de fondo, para no perderse nunca:** **Claude nunca guarda nada de forma definitiva.** Su
trabajo es investigar y empaquetar. Laravel es la única fuente de verdad: coches, clientes y
expedientes viven ahí, no en el chat.

---

## 1. Los dos flujos de trabajo

Todo lo que haces encaja en uno de estos dos caminos:

- **Flujo 1 — Tienes un coche** (lo has encontrado tú, con o sin cliente esperándolo). Le pasas
  el anuncio a Claude, lo investiga y te da un veredicto.
- **Flujo 2 — Tienes un cliente** que quiere un coche pero no sabe cuál exactamente. Le cuentas a
  Claude lo que te ha dicho el cliente y Claude le busca opciones en el mercado alemán y español.

Los dos acaban en el mismo sitio: **un documento que subes a Laravel**.

---

## 2. Flujo 1 — Evaluar un coche concreto, paso a paso

### Paso 1 — Pásale el anuncio a Claude

Lo mejor es darle el **enlace** del anuncio (mobile.de, AutoScout24, Coches.net...), no una
captura de pantalla — el enlace permite a Claude leer toda la ficha, no solo lo que se ve en la
foto. Si solo tienes una captura, también funciona, pero es la segunda opción.

Solo con pegar el enlace y decir algo como *"mira este coche"* o *"evalúa este anuncio"*, Claude
arranca solo.

### Paso 2 — Claude te da un primer vistazo rápido (el filtro de margen)

Antes de ponerse a investigar en profundidad, Claude hace un cálculo rápido: precio del anuncio +
transporte + trámites + impuestos + tus honorarios, y lo compara con el precio de mercado o con
el presupuesto del cliente.

- Si el margen es claramente malo, te lo dice ahí mismo y **para** — no pierde media hora
  investigando un coche que no compensa.
- Si el margen tiene sentido, sigue directo a la investigación completa, sin que tengas que decir
  nada.

### Paso 3 — Investigación a fondo (9 aspectos)

Si el coche pasa el filtro, Claude investiga: averías típicas de ese motor, recalls oficiales
pendientes, precio de mercado real (varios anuncios comparables), fiabilidad, riesgo de
homologación en España, distintivo ambiental de la DGT, seguro estimado, coste de piezas, y
cualquier detalle propio de esa unidad.

Si ya se investigó antes un coche del mismo modelo, Claude reutiliza lo que sigue vigente (te lo
dirá explícitamente: *"esto viene de caché, es de hace X meses"*) y solo investiga desde cero lo
que falte. Esto hace que el segundo Opel Astra que mires sea mucho más rápido que el primero.

### Paso 4 — Claude te presenta el resumen y **espera tu OK**

Aquí es donde tú decides. Claude te enseña:

- Los datos del coche y qué campos no pudo rellenar (por ejemplo, el VIN si el vendedor aún no lo
  ha dado).
- Todo lo que investigó, **con la fuente de cada dato**.
- Cómo se posiciona el precio frente al mercado.
- El margen con números claros: coste total puesto en España, tus honorarios, precio final al
  cliente.
- Banderas rojas si las hay.
- Su veredicto: recomienda comprar, comprar si baja de precio, dudoso, o descartar — y por qué.

**Claude nunca genera nada sin que tú digas que sí.** Si algo no cuadra o quieres que mire otra
cosa, se lo dices y vuelve atrás. Si estás de acuerdo, le dices que genere el paquete.

### Paso 5 — Se genera el paquete (.zip)

Con tu OK, Claude prepara un archivo `.zip` que contiene:

- El informe completo del coche (los datos que se van a importar a Laravel).
- El **informe interno** en PDF — para vosotros: lleva precio de compra, honorarios y margen.
- La **ficha publicitaria** en PDF — para enseñar al cliente o publicar: solo precio final, con un
  QR que lleva directo al formulario de solicitud por si alguien la ve y quiere otro coche.
- Todas las fotos del anuncio.

Este `.zip` queda guardado en tu carpeta, listo para subir.

### Paso 6 — Subir el paquete a Laravel

1. Entra en `https://jjimportmotors.on-forge.com/cars/import-valuation`.
2. Ve a la pestaña **Subir ZIP**.
3. Selecciona el archivo `.zip` que te ha dado Claude.
4. Laravel lo hace todo solo: crea o actualiza la ficha del coche, adjunta los dos PDFs al
   expediente, y sube las fotos a la galería.

Si vuelves a subir el mismo coche más adelante (por ejemplo, porque llegó el VIN), Laravel
**sustituye** las fotos e informes anteriores — no se duplica nada.

### Paso 7 — Si llegan datos nuevos después (VIN, factura del precio de coche nuevo...)

Es normal que el VIN o el precio de coche nuevo lleguen después de contactar con el vendedor.
Cuando pase, dáselo a Claude: repite solo la parte que cambia (por ejemplo, recalcula el impuesto
de matriculación con el nuevo dato), actualiza el veredicto si hace falta, y vuelve a generar el
paquete. Al subirlo, Laravel reconoce que es el mismo coche y lo actualiza, no crea uno nuevo.

---

## 3. Flujo 2 — Un cliente no sabe qué coche quiere

### Paso 1 — Cuéntale a Claude cómo es el cliente

Dile lo que sepas: qué te ha pedido, presupuesto, para qué lo quiere. Si te faltan datos
importantes, Claude te va a preguntar antes de buscar nada — y con razón: una búsqueda mal acotada
da opciones que no sirven. Las preguntas clave que no pueden faltar:

- **Presupuesto**, y sobre todo: ¿es el precio del coche, o el precio final puesto en España? Es
  la confusión que más dinero cuesta.
- **Para qué lo va a usar** (ciudad, viajes largos, familia...).
- **Kilómetros al año**, porque decide mejor el combustible que la preferencia del cliente.
- **Cuándo lo necesita** — una importación no es cosa de una semana.

### Paso 2 — Claude propone modelos, no anuncios

Antes de salir a buscar coches concretos, Claude te propone 3-5 **modelos** que encajan,
incluyendo alguno que el cliente no habría pensado pero que le viene mejor. Tú das el visto bueno
de qué modelos rastrear.

### Paso 3 — Claude rastrea el mercado

Busca en Alemania (mobile.de, AutoScout24) sobre todo, porque hay más oferta y mejor documentada,
y usa el mercado español como referencia de precio de reventa. Descarta los que no cumplen el
presupuesto real (coche + todos los gastos, no solo el precio del anuncio).

### Paso 4 — Te presenta 3-5 finalistas con su recomendación

Para cada uno: precio y coste total en España, año, kilómetros, qué cumple de lo que pidió el
cliente y qué no, y su opinión. Siempre te dice **cuál miraría primero y por qué**.

### Paso 5 — Se genera la comparativa en PDF

Igual que en el Flujo 1, una vez lo habéis revisado juntos, Claude genera un PDF de marca con las
opciones, la recomendación destacada, y el contacto con QR — listo para reenviar al cliente. Este
documento **nunca lleva tus honorarios ni tu margen**, solo el precio final: es un documento para
que lo vea cualquiera.

### Paso 6 — Si el cliente elige uno

A partir de ahí sigues el **Flujo 1** normal con ese anuncio concreto.

### Una cosa importante

**Los clientes se dan de alta en Laravel (`/clients`), no en el chat con Claude.** Claude te ayuda
a decidir y a redactar, pero la ficha del cliente la creas o editas tú en la web. Si le das a
Claude el número de cliente de Laravel, quedará enlazado al coche cuando generes el paquete.

---

## 4. Otro documento útil: presentarte a un cliente nuevo

Si tienes un cliente que todavía no sabe nada de cómo trabajáis, puedes pedirle a Claude el
**dossier de empresa**: un PDF de 2 páginas que explica qué hacéis, el proceso en 6 pasos, qué
incluye el precio, y por qué elegiros — con un QR y un botón que llevan directos al formulario de
solicitud. Es el documento de primer contacto, antes de hablar de un coche concreto.

---

## 5. Preguntas frecuentes

**¿Tengo que revisar todo lo que dice Claude antes de subir algo?**

Sí, siempre. Claude presenta su investigación y su veredicto y espera tu confirmación explícita
antes de generar nada. Nunca sube nada a Laravel por su cuenta — eso lo haces tú, a mano, en la
pestaña de subir ZIP.

**¿Qué pasa si Claude no encuentra un dato (el VIN, el CO₂...)?**

Lo deja marcado como pendiente en vez de inventarlo. Te lo señalará al presentarte el resumen.

**¿Puedo confiar en los precios de mercado que da Claude?**

Cada comparable lleva su URL para que lo puedas comprobar tú mismo. Claude verifica que el anuncio
siga activo antes de citarlo, pero conviene echarle un vistazo si algo te llama la atención.

**¿Qué diferencia hay entre el informe interno y la ficha publicitaria?**

El informe interno lleva precio de compra, honorarios y margen — es solo vuestro. La ficha
publicitaria y la comparativa de cliente llevan solo el precio final — son las que puede ver
cualquiera. **Nunca se mezclan.**

**¿Qué hago si subo un ZIP y algo sale mal?**

Vuelve a Claude y cuéntaselo. La copia del informe queda siempre guardada, así que no se pierde el
trabajo de investigación aunque falle algo al generar o al subir el paquete.

**¿Puedo pedirle a Claude que reutilice información de un coche que ya evalué?**

Sí — si es el mismo modelo, gran parte de la investigación (averías, fiabilidad, homologación...)
ya está guardada y Claude la reutiliza sola, dejándote claro qué es nuevo y qué es reciclado.
