<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();
</script>

<template>
    <Head title="Guía de Uso" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Guía de Uso</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader title="Guía de Uso" subtitle="Sistema JJ Import Motors (Claude + Laravel)" />

                <div class="prose max-w-none text-gray-700">
                    <h2>Guía de uso — Sistema JJ Import Motors (Claude + Laravel)</h2>

                    <p>Esta guía explica cómo trabajar cada día con las dos piezas del sistema: <strong>Claude</strong> (que investiga, valora y prepara los documentos) y la <strong>web de Laravel</strong> (donde vive de verdad el inventario y los clientes). No es para clientes — es la guía interna de cómo tú usas la herramienta.</p>

                    <h3>Idea de fondo, para no perderse nunca:</h3>
                    <p><strong>Claude nunca guarda nada de forma definitiva.</strong> Su trabajo es investigar y empaquetar. Laravel es la única fuente de verdad: coches, clientes y expedientes viven ahí, no en el chat.</p>

                    <h2>1. Los dos flujos de trabajo</h2>

                    <p>Todo lo que haces encaja en uno de estos dos caminos:</p>

                    <p><strong>Flujo 1 — Tienes un coche</strong> (lo has encontrado tú, con o sin cliente esperándolo). Le pasas el anuncio a Claude, lo investiga y te da un veredicto.</p>

                    <p><strong>Flujo 2 — Tienes un cliente</strong> que quiere un coche pero no sabe cuál exactamente. Le cuentas a Claude lo que te ha dicho el cliente y Claude le busca opciones en el mercado alemán y español.</p>

                    <p>Los dos acaban en el mismo sitio: un documento que subes a Laravel.</p>

                    <h2>2. Flujo 1 — Evaluar un coche concreto, paso a paso</h2>

                    <h3>Paso 1 — Pásale el anuncio a Claude</h3>

                    <p>Lo mejor es darle el <strong>enlace</strong> del anuncio (mobile.de, AutoScout24, Coches.net...), no una captura de pantalla — el enlace permite a Claude leer toda la ficha, no solo lo que se ve en la foto. Si solo tienes una captura, también funciona, pero es la segunda opción.</p>

                    <p>Solo con pegar el enlace y decir algo como "mira este coche" o "evalúa este anuncio", Claude arranca solo.</p>

                    <h3>Paso 2 — Claude te da un primer vistazo rápido (el filtro de margen)</h3>

                    <p>Antes de ponerse a investigar en profundidad, Claude hace un cálculo rápido: precio del anuncio + transporte + trámites + impuestos + tus honorarios, y lo compara con el precio de mercado o con el presupuesto del cliente.</p>

                    <ul>
                        <li>Si el margen es claramente malo, te lo dice ahí mismo y <strong>para</strong> — no pierde media hora investigando un coche que no compensa.</li>
                        <li>Si el margen tiene sentido, sigue directo a la investigación completa, sin que tengas que decir nada.</li>
                    </ul>

                    <h3>Paso 3 — Investigación a fondo (9 aspectos)</h3>

                    <p>Si el coche pasa el filtro, Claude investiga: averías típicas de ese motor, recalls oficiales pendientes, precio de mercado real (varios anuncios comparables), fiabilidad, riesgo de homologación en España, distintivo ambiental de la DGT, seguro estimado, coste de piezas, y cualquier detalle propio de esa unidad.</p>

                    <p>Si ya se investigó antes un coche del mismo modelo, Claude reutiliza lo que sigue vigente (te lo dirá explícitamente: "esto viene de caché, es de hace X meses") y solo investiga desde cero lo que falte. Esto hace que el segundo Opel Astra que mires sea mucho más rápido que el primero.</p>

                    <h3>Paso 4 — Claude te presenta el resumen y <strong>espera tu OK</strong></h3>

                    <p>Aquí es donde tú decides. Claude te enseña:</p>

                    <ul>
                        <li>Los datos del coche y qué campos no pudo rellenar (por ejemplo, el VIN si el vendedor aún no lo ha dado).</li>
                        <li>Todo lo que investigó, con la fuente de cada dato.</li>
                        <li>Cómo se posiciona el precio frente al mercado.</li>
                        <li>El margen con números claros: coste total puesto en España, tus honorarios, precio final al cliente.</li>
                        <li>Banderas rojas si las hay.</li>
                        <li>Su veredicto: recomienda comprar, comprar si baja de precio, dudoso, o descartar — y por qué.</li>
                    </ul>

                    <p><strong>Claude nunca genera nada sin que tú digas que sí.</strong> Si algo no cuadra o quieres que mire otra cosa, se lo dices y vuelve atrás. Si estás de acuerdo, le dices que genere el paquete.</p>

                    <h3>Paso 5 — Se genera el paquete (.zip)</h3>

                    <p>Con tu OK, Claude prepara un archivo <code>.zip</code> que contiene:</p>

                    <ul>
                        <li>El informe completo del coche (los datos que se van a importar a Laravel).</li>
                        <li>El <strong>informe interno</strong> en PDF — para vosotros: lleva precio de compra, honorarios y margen.</li>
                        <li>La <strong>ficha publicitaria</strong> en PDF — para enseñar al cliente o publicar: solo precio final, con un QR que lleva directo al formulario de solicitud por si alguien la ve y quiere otro coche.</li>
                        <li>Todas las fotos del anuncio.</li>
                    </ul>

                    <p>Este <code>.zip</code> queda guardado en tu carpeta, listo para subir.</p>

                    <h3>Paso 6 — Subir el paquete a Laravel</h3>

                    <ol>
                        <li>Entra en <code>https://jjimportmotors.on-forge.com/cars/import-valuation</code>.</li>
                        <li>Ve a la pestaña <strong>Subir ZIP</strong>.</li>
                        <li>Selecciona el archivo <code>.zip</code> que te ha dado Claude.</li>
                        <li>Laravel lo hace todo solo: crea o actualiza la ficha del coche, adjunta los dos PDFs al expediente, y sube las fotos a la galería.</li>
                    </ol>

                    <p>Si vuelves a subir el mismo coche más adelante (por ejemplo, porque llegó el VIN), Laravel <strong>sustituye</strong> las fotos e informes anteriores — no se duplica nada.</p>

                    <h3>Paso 7 — Si llegan datos nuevos después (VIN, factura del precio de coche nuevo...)</h3>

                    <p>Es normal que el VIN o el precio de coche nuevo lleguen después de contactar con el vendedor. Cuando pase, dáselo a Claude: repite solo la parte que cambia (por ejemplo, recalcula el impuesto de matriculación con el nuevo dato), actualiza el veredicto si hace falta, y vuelve a generar el paquete. Al subirlo, Laravel reconoce que es el mismo coche y lo actualiza, no crea uno nuevo.</p>

                    <h2>3. Flujo 2 — Un cliente no sabe qué coche quiere</h2>

                    <h3>Paso 1 — Cuéntale a Claude cómo es el cliente</h3>

                    <p>Dile lo que sepas: qué te ha pedido, presupuesto, para qué lo quiere. Si te faltan datos importantes, Claude te va a preguntar antes de buscar nada — y con razón: una búsqueda mal acotada da opciones que no sirven. Las preguntas clave que no puede faltar:</p>

                    <ul>
                        <li><strong>Presupuesto</strong>, y sobre todo: ¿es el precio del coche, o el precio final puesto en España? Es la confusión que más dinero cuesta.</li>
                        <li><strong>Para qué lo va a usar</strong> (ciudad, viajes largos, familia...).</li>
                        <li><strong>Kilómetros al año</strong>, porque decide mejor el combustible que la preferencia del cliente.</li>
                        <li><strong>Cuándo lo necesita</strong> — una importación no es cosa de una semana.</li>
                    </ul>

                    <h3>Paso 2 — Claude propone modelos, no anuncios</h3>

                    <p>Antes de salir a buscar coches concretos, Claude te propone 3-5 <strong>modelos</strong> que encajan, incluyendo alguno que el cliente no habría pensado pero que le viene mejor. Tú das el visto bueno de qué modelos rastrear.</p>

                    <h3>Paso 3 — Claude rastrea el mercado</h3>

                    <p>Busca en Alemania (mobile.de, AutoScout24) sobre todo, porque hay más oferta y mejor documentada, y usa el mercado español como referencia de precio de reventa. Descarta los que no cumplen el presupuesto real (coche + todos los gastos, no solo el precio del anuncio).</p>

                    <h3>Paso 4 — Te presenta 3-5 finalistas con su recomendación</h3>

                    <p>Para cada uno: precio y coste total en España, año, kilómetros, qué cumple de lo que pidió el cliente y qué no, y su opinión. Siempre te dice cuál miraría primero y por qué.</p>

                    <h3>Paso 5 — Se genera la comparativa en PDF</h3>

                    <p>Igual que en el Flujo 1, una vez lo habéis revisado juntos, Claude genera un PDF de marca con las opciones, la recomendación destacada, y el contacto con QR — listo para reenviar al cliente. Este documento <strong>nunca lleva tus honorarios ni tu margen</strong>, solo el precio final: es un documento para que lo vea cualquiera.</p>

                    <h3>Paso 6 — Si el cliente elige uno</h3>

                    <p>A partir de ahí sigues el Flujo 1 normal con ese anuncio concreto.</p>

                    <h3>Una cosa importante</h3>

                    <p><strong>Los clientes se dan de alta en Laravel (<code>/clients</code>), no en el chat con Claude.</strong> Claude te ayuda a decidir y a redactar, pero la ficha del cliente la creas o editas tú en la web. Si le das a Claude el número de cliente de Laravel, quedará enlazado al coche cuando generes el paquete.</p>

                    <h2>4. Otro documento útil: presentarte a un cliente nuevo</h2>

                    <p>Si tienes un cliente que todavía no sabe nada de cómo trabajáis, puedes pedirle a Claude el <strong>dossier de empresa</strong>: un PDF de 2 páginas que explica qué hacéis, el proceso en 6 pasos, qué incluye el precio, y por qué elegiros — con un QR y un botón que llevan directos al formulario de solicitud. Es el documento de primer contacto, antes de hablar de un coche concreto.</p>

                    <h2>5. Preguntas frecuentes</h2>

                    <p><strong>¿Tengo que revisar todo lo que dice Claude antes de subir algo?</strong></p>
                    <p>Sí, siempre. Claude presenta su investigación y su veredicto y espera tu confirmación explícita antes de generar nada. Nunca sube nada a Laravel por su cuenta — eso lo haces tú, a mano, en la pestaña de subir ZIP.</p>

                    <p><strong>¿Qué pasa si Claude no encuentra un dato (el VIN, el CO₂...)?</strong></p>
                    <p>Lo deja marcado como pendiente en vez de inventarlo. Te lo señalará al presentarte el resumen.</p>

                    <p><strong>¿Puedo confiar en los precios de mercado que da Claude?</strong></p>
                    <p>Cada comparable lleva su URL para que lo puedas comprobar tú mismo. Claude verifica que el anuncio siga activo antes de citarlo, pero conviene echarle un vistazo si algo te llama la atención.</p>

                    <p><strong>¿Qué diferencia hay entre el informe interno y la ficha publicitaria?</strong></p>
                    <p>El informe interno lleva precio de compra, honorarios y margen — es solo vuestro. La ficha publicitaria y la comparativa de cliente llevan solo el precio final — son las que puede ver cualquiera. Nunca se mezclan.</p>

                    <p><strong>¿Qué hago si subo un ZIP y algo sale mal?</strong></p>
                    <p>Vuelve a Claude y cuéntaselo. La copia del informe queda siempre guardada, así que no se pierde el trabajo de investigación aunque falle algo al generar o al subir el paquete.</p>

                    <p><strong>¿Puedo pedirle a Claude que reutilice información de un coche que ya evalué?</strong></p>
                    <p>Sí — si es el mismo modelo, gran parte de la investigación (averías, fiabilidad, homologación...) ya está guardada y Claude la reutiliza sola, dejándote claro qué es nuevo y qué es reciclado.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
