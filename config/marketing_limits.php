<?php

/*
 * Límites de marketing por canal — fuente única (B1 auditoría 09-sep-2026).
 *
 * Antes estos límites vivían en tres sitios que no coincidían:
 *   - copy_engine.md de la skill (5 hashtags IG, ≤4 TikTok, ≤2 FB)
 *   - check_marketing.py (mismos)
 *   - Marketing.vue (10 IG, 5 TikTok, 5 FB) ← desajuste
 *
 * Ahora: este config es la fuente canónica. El backend lo expone al front
 * como props de Inertia (ver Marketing.vue), y la skill lleva una copia
 * versionada en .claude/skills/importacion-vehiculos/marketing_limits.json
 * que check_marketing.py lee.
 *
 * Cada canal define longitudes máximas de título/descripción y número
 * máximo de hashtags. `max_hashtags = 0` significa que el canal no usa
 * hashtags (es un portal de coches).
 */

return [
    'instagram' => [
        'title_max' => 125,
        'desc_max' => 2200,
        'max_hashtags' => 5,
        'help' => 'Mejor 3-5 hashtags. Hashtags de marca + nicho + emoción.',
    ],
    'tiktok' => [
        'title_max' => 150,
        'desc_max' => 2200,
        'max_hashtags' => 4,
        'help' => 'Hasta 4 hashtags. Viralidad alta.',
    ],
    'facebook' => [
        'title_max' => 100,
        'desc_max' => 2200,
        'max_hashtags' => 2,
        'help' => 'Facebook premia contenido masivo. 1-2 hashtags máximo.',
    ],
    'milanuncios' => [
        'title_max' => 60,
        'desc_max' => 4000,
        'max_hashtags' => 0,
        'help' => 'Portal de anuncios. Sin hashtags.',
    ],
    'coches_net' => [
        'title_max' => 60,
        'desc_max' => 4000,
        'max_hashtags' => 0,
        'help' => 'Portal de anuncios. Sin hashtags. Título máx. 60 chars.',
    ],
    'wallapop' => [
        'title_max' => 80,
        'desc_max' => 3000,
        'max_hashtags' => 0,
        'help' => 'Wallapop: descripción recortada a 600-900 chars por el portal.',
    ],
];
