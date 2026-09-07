# Cómo funciona el sistema de JJ Import Motors

Guía sencilla, sin tecnicismos, de cómo usar el sistema en el día a día: qué haces tú, qué hace el chat, y qué ves en el panel.

> ⚠️ **15-ago-2026 — Arquitectura actualizada.** La conexión por Google Drive que se describe abajo es el flujo **legacy** del panel. El flujo actual: la investigación se hace en el **chat** (skill `importacion-vehiculos`), se genera el paquete (JSON + esqueletos `.txt` + fotos reales), se sube a **Laravel (importnexcore)** — que es el repositorio único — y el panel web muestra los datos de Laravel. Drive queda solo como respaldo histórico.

> 📌 **Documentos relacionados** (en esta carpeta):
> - **`CONTEXTO_ACTIVO.md`** — contexto maestro para Claude (tono de marca, qué hace/no hace, reglas duras). **Léelo primero.**
> - **`CONTEXTO_FOTOS.md`** — galería de referencia de coches reales fotografiados
> - `Flujo_Operativo_JJ_Import_Motors.md` — referencia técnica detallada (arquitectura, fases)
> - `_archive/Lecciones_*.md` — lecciones aprendidas de decisiones ya ejecutadas

## Las dos partes del sistema

Hay dos sitios donde trabajas, cada uno para una cosa distinta:

**El chat (aquí, hablando conmigo):** es donde le enseñas un anuncio de coche o me cuentas sobre un cliente nuevo, y donde yo investigo en internet (fiabilidad, averías típicas, si tiene alguna campaña de revisión pendiente, y a qué precio se venden coches parecidos). Aquí es donde se prepara todo.

**El panel web:** es la pantalla que usas para el día a día — ver en qué punto está cada coche, mover el estado de una operación, apuntar notas, ver cuánto vas a ganar. Todo lo que preparamos en el chat aparece aquí solo, sin que tengas que copiar nada a mano.

Las dos partes están conectadas a través de tu Google Drive, así que lo que se guarda desde un sitio se ve en el otro.

## Cuando encuentras un coche para ofrecer

Esto pasa cuando tú encuentras un coche interesante (en mobile.de, AutoScout24, Coches.net...) y todavía no tienes un cliente concreto para él.

**1. Me enseñas el anuncio.** Me pasas una foto, una captura de pantalla o el enlace del anuncio.

**2. Yo extraigo los datos.** Leo el anuncio y apunto marca, modelo, año, kilómetros, precio, quién lo vende, etc.

**3. Yo investigo el coche en internet.** Busco cinco cosas y siempre te digo de dónde saqué cada dato:
   - Qué averías o problemas son típicos de ese motor.
   - Si hay alguna campaña de revisión oficial pendiente (lo que se conoce como "recall").
   - A qué precio se están vendiendo coches parecidos ahora mismo.
   - Qué opinión tienen en general los dueños de ese modelo.
   - Cualquier otro dato que me parezca importante sobre ese coche en concreto.

**4. Te enseño un resumen y espero tu confirmación.** Nunca genero nada sin que tú me digas que sí. Te enseño los datos del coche, lo que he investigado, y si el precio del anuncio está bien o mal comparado con el mercado. Si algo no te cuadra, lo revisamos antes de seguir.

**5. Cuando confirmas, preparo todo automáticamente:**
   - Escribo el texto del anuncio para publicarlo en redes sociales o portales.
   - Genero una ficha completa del coche en un documento de Excel, con todos los números: lo que cuesta traerlo, tus honorarios, y el precio final para el cliente.
   - Genero un resumen de una página en PDF con lo más importante, listo para enseñar a un cliente.
   - Añado el coche a tu lista de coches disponibles.
   - Hago que el coche aparezca en el panel web automáticamente.

**6. Te doy los enlaces a todo lo que se ha creado**, ya guardado en tu Google Drive — no hace falta que descargues ni subas nada a mano.

En cuanto termino este proceso, si tienes el panel abierto, solo tienes que pulsar el botón "Recargar datos de Drive" para verlo ahí.

## Cuando un cliente te pide un coche

Esto pasa cuando alguien te contacta buscando un tipo de coche concreto.

**1. Me cuentas del cliente.** Con lo que tengas es suficiente — nombre, cómo te contactó, qué busca, presupuesto. No hace falta tenerlo todo, se puede completar más adelante.

**2. Genero su ficha.** Se guarda junto con el resto de tus clientes, y aparece automáticamente en el panel.

**3. Busco coincidencias en tu inventario.** Reviso qué coches tienes disponibles ahora mismo que encajen con lo que busca (por presupuesto, modelo, kilómetros...) y te los enseño para que decidas si se los propones. Yo nunca le escribo directamente al cliente — solo te preparo la información para que tú decidas.

**4. Cuando el cliente elige un coche**, actualizamos su ficha, el coche pasa a estar reservado para él, y todo queda reflejado en el panel.

## Cómo se usa el panel en el día a día

Cuando abres el panel, ves los coches y clientes organizados en tarjetas, listas o un tablero tipo "kanban" (columnas por estado, donde puedes arrastrar cada coche de una fase a la siguiente: localizado, valorando, ofrecido, comprado, entregado...).

Desde ahí puedes:
- Cambiar el estado de un coche o cliente.
- Marcar qué pasos ya has hecho (checklist: señal pagada, transporte contratado, ITV hecha...).
- Apuntar notas.
- Ver cuánto vas a ganar en cada operación y en total.
- Ver los coches en un mapa según de dónde vienen.
- Sacar plantillas de mensajes ya escritas para contactar al vendedor o al cliente, en español, inglés o alemán.
- Dar de alta un coche o un cliente rápido sin pasar por el chat.

**Importante — el botón "Guardar cambios":** cuando editas algo en el panel (cambias un estado, marcas una casilla, escribes una nota...), ese cambio no se sube solo al momento. Se queda "en espera" hasta que pulsas el botón **"💾 Guardar cambios"** arriba del todo. Este botón te dice cuántos cambios tienes pendientes. Acuérdate de pulsarlo antes de cerrar la pestaña — si intentas cerrarla con cambios sin guardar, el panel te avisa.

**El botón "Recargar datos de Drive":** úsalo cuando acabes de generar algo nuevo desde el chat (un coche, un cliente) y quieras verlo aparecer en el panel sin tener que cerrar y volver a abrir la página. Si tienes cambios pendientes de guardar, te preguntará primero si quieres guardarlos o descartarlos, para que no se pierda nada.

## Cosas a tener en cuenta

- **Todo se guarda en tu Google Drive**, en las carpetas de coches y de clientes que ya tenías. No hace falta guardar nada en tu ordenador ni enviarte archivos por otro sitio.
- **La investigación real (fiabilidad, campañas de revisión, precio de mercado) solo se puede hacer aquí en el chat.** El panel es para consultar y organizar, no investiga por su cuenta.
- **De vez en cuando se acumulan copias de más en Drive.** Es una limitación de cómo está conectado el sistema a Drive (no hay forma de "sobrescribir" un archivo, solo de crear uno nuevo cada vez que se guarda algo). El botón "Guardar cambios" del panel ayuda a que esto pase mucho menos, pero si alguna vez ves varias copias del mismo archivo en Drive, dímelo y te digo exactamente cuáles borrar.
