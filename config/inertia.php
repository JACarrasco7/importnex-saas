<?php

/*
 * Solo se sobrescribe la ruta de las páginas: el proyecto usa `js/Pages`
 * (mayúscula) y el valor por defecto del paquete es `js/pages`. En Windows da
 * igual, pero en Linux (servidor, CI) los tests de Inertia no encontraban las
 * páginas. El resto de opciones se heredan del paquete.
 */

return [

    'pages' => [

        'ensure_pages_exist' => false,

        'paths' => [
            resource_path('js/Pages'),
        ],

        'extensions' => ['js', 'jsx', 'svelte', 'ts', 'tsx', 'vue'],

    ],

];
