<?php

// Límites de longitud y hashtags por canal, usados por Marketing.vue como
// guía en el editor (contador de caracteres, aviso de cupo de hashtags).
//
// FUENTE ÚNICA — estos números son los mismos que valida
// .claude/skills/importacion-vehiculos/scripts/check_marketing.py (arrays
// `bandas` y `MAX_HASHTAGS`) sobre el texto que genera la skill. Si cambias
// uno, cambia el otro: que la skill y el panel diverjan sin avisar es
// exactamente el patrón que costó una semana en el fix de v3.9.2 (ver
// memoria/MEMORIA.md — el 21% de IVA fantasma y los 4 canales de portal con
// el mismo texto fueron ambos por tener dos fuentes de la misma regla).
//
// title_max se omite a propósito para milanuncios/coches_net/wallapop: no
// hay ninguna fuente fiable en este repo de cuál es el límite real de
// título de cada portal (no está documentado en copy_engine.md ni en
// ningún sitio); en vez de inventar una cifra, Marketing.vue cae al
// genérico (100) cuando no se especifica. Si algún día se confirma el
// límite real de un portal, se añade aquí.

return [

    'milanuncios' => [
        'desc_min' => 800,
        'desc_max' => 3000,
        'max_hashtags' => 0,
        'help' => 'Texto base del portal. Milanuncios no tiene un límite estricto de caracteres conocido; el rango es una guía de calidad, no un tope técnico.',
    ],

    'coches_net' => [
        'desc_min' => 800,
        'desc_max' => 3000,
        'max_hashtags' => 0,
        'help' => 'Mismo texto base que Milanuncios. El rango es una guía de calidad, no un tope técnico del portal.',
    ],

    // Wallapop es un RECORTE del texto base, nunca el texto completo del
    // portal — desde v3.9.2, ValuationPackageIngestor::recortarParaWallapop()
    // lo fuerza a 600-900 caracteres, protegiendo siempre la pega honesta y
    // el aviso legal completo. La guía tiene que reflejar ESE rango (no el
    // de "portal" genérico de arriba), o el operador pensaría que le falta
    // texto cuando en realidad está correcto.
    'wallapop' => [
        'desc_min' => 600,
        'desc_max' => 900,
        'max_hashtags' => 0,
        'help' => 'Wallapop es un recorte del texto base (600-900 caracteres), no el texto completo del portal — así se genera automáticamente al importar el ZIP.',
    ],

    'tiktok' => [
        'desc_min' => 200,
        'desc_max' => 800,
        'max_hashtags' => 4,
        'help' => 'Caption del vídeo corto (no el guion). Ventana crítica de gancho: 0-3 s.',
    ],

    'instagram' => [
        'desc_min' => 600,
        'desc_max' => 2200,
        'max_hashtags' => 5,
        'help' => 'Límite técnico real de Instagram: 2200 caracteres en el pie de publicación.',
    ],

    'facebook' => [
        'desc_min' => 400,
        'desc_max' => 1500,
        'max_hashtags' => 2,
        'help' => 'Guía para publicaciones/stories de la página. La ficha de Facebook Marketplace usa bloques propios (FBMP_*) y comparte esta misma guía por ahora.',
    ],

];
