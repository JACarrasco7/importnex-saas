# Biblioteca de ganchos — `07-marketing/biblioteca_ganchos.md`

> **8 ángulos de venta.** Cada ángulo lleva el campo del `informe.json` que lo
> sostiene (proof point). Si el campo está vacío, ese ángulo no se usa con ese
> coche.

---

## 1 · Los 8 ángulos

| # | Ángulo | Proof point (campo del informe.json) | Cuándo usarlo |
|---|---|---|---|
| 1 | **Rareza** ("casi no se ven los tres a la vez") | `vehiculo.{anio, km, propietarios}` | Coches con km bajo para su año y un solo dueño |
| 2 | **Ahorro vs mercado** | `comparables[].precio_mediana` + `precio_cliente` | Coches cuyo precio queda muy por debajo de la mediana |
| 3 | **Edición especial** | `vehiculo.equipamiento[]` + `vehiculo.version` | Ediciones limitadas, colores raros, paquetes M-Sport / AMG / OPC / S-line / FR |
| 4 | **Mecánica robusta** | `investigacion.aspectos.fiables` + `fiabilidad` | Motores conocidos por su fiabilidad (BMW N47/B47, MB OM651, etc.) |
| 5 | **Baja importación vs España** | `comparables[].precio_mediana_es` vs `precio_origen_de` | Alemanes más baratos que en España por mercado |
| 6 | **Recién revisado** | `coche_id` (fecha revisión) + `aspectos[]=favorable` | Coches revisados en los últimos 7 días |
| 7 | **Etiqueta ambiental favorable** | `vehiculo.etiqueta_dgt` (C o ECO) | Coches con C/ECO (ZBE sin restricciones) |
| 8 | **Kilometraje bajo verificado** | `vehiculo.km` + `investigacion.aspectos.kilometraje` | Coches con kilometraje verificable y bajo |

---

## 2 · Aperturas prohibidas (A24)

**Nunca** empezar el gancho con:

- `¡Brutal!`, `¡Increíble!`, `¡Espectacular!`, `¡No te lo pierdas!`
- `Chollo`, `Oportunidad única`, `Última oportunidad`, `Una vez en la vida`
- `Lo que estabas esperando`, `Por fin`, `Ya está aquí`, `Ya llegó`
- `Date prisa`, `Corre`, `No esperes`, `Última unidad`

**Por qué:** bajan la calidad percibida y filtramos al público que no es nuestro
target. El gancho de JJ Import Motors **informa**, no emociona artificialmente.

---

## 3 · Banco de cierres

Cada `[IG_CTA]` / `[FB_CTA]` / `[VT_CTA]` debe nombrar al destinatario:

- "Te paso la ficha completa por DM." (IG)
- "Escríbenos por WhatsApp al +34 ..." (FB)
- "Comenta MARCA + MODELO y te paso la ficha." (TikTok)
- "Por mensaje en Marketplace." (FB Marketplace — NUNCA teléfono)
- "A través del formulario del portal." (Coches.net / Milanuncios / Wallapop)

**Regla (sends per reach):** un cierre que nombra al destinatario concreto
("mándaselo a quien lleve medio año buscando uno") mejora el reach porque
incrementa `sends per reach`, hoy una de las señales de ranking más fuertes
de Instagram (3-5 % es una referencia buena — fuente: influencer marketing hub).

---

## 4 · Cómo declarar la pega honesta

Regla dura A28: toda pieza larga declara un punto flojo con su solución.

**Fórmula:**
```
⚠️ <hecho verificable>: <solución concreta>
```

**Ejemplos buenos:**
- `⚠️ Neumáticos al 50 %: se sustituyen antes de la entrega.`
- `⚠️ Roce en el paragolpes trasero, visible en la foto 7. Descontado del precio.`
- `⚠️ ITV a pasar antes de la entrega: ya tenemos cita.`

**Si no hay pega real:** se sustituye por "se ha revisado qué para poder
afirmarlo". Ejemplo: `✅ ITV pasada, 0 defectos en inspección de 150 puntos`.

Recibes menos llamadas, pero **mucho mejores**, y hace creíble el resto del
anuncio.

---

## 5 · Tono por situación (estilo brand-review)

| Situación | Cómo se trata |
|---|---|
| **Pega grave** (accidente declarado, kilometraje alto) | Directo, sin minimizar. Solución visible. Sin excusas. |
| **Unidad ya vendida / reservada** | "Esta unidad ya está gestionada para otro cliente. Te aviso cuando entre otra similar." (sin disculpas excesivas) |
| **Sube el coste** (transporte, ITV, COC) | Anticipar: "estos son los conceptos y por qué" antes de que el cliente pregunte |
| **Comparación con otro importador** | Nunca hablar mal de la competencia. Solo destacar lo nuestro: "lo que incluimos / lo que no incluimos" |
| **Cliente que no contestó** | "Sigo aquí por si te interesa" sin presionar (1 recordatorio máximo en 7 días) |

---

## 6 · Contenido de marca mensual (sin coche que publicar)

4 formatos recurrentes para mantener el perfil vivo:

1. **"Dato del día"** — 1 cifra curiosa del mercado alemán vs español.
2. **"Caso real"** — fragmento de un `[PT_QUE_INCLUYE]` anonimizado.
3. **"Mecánica explicada"** — 1 problema común + cómo lo detecta JJ Import Motors.
4. **"Comparativa de mercado"** — el [PT_FICHA] de un modelo genérico con datos reales.

Cadencia: 2-3 posts/semana + 1 reel/mes.
