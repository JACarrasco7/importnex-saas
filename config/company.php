<?php

/*
 * Datos de contacto de JJ Import Motors, centralizados para reutilizar en
 * cualquier sitio que necesite mostrar el mismo pie de contacto (marketing,
 * folletos, contratos). Fuente única de verdad: cambiar aquí actualiza
 * automáticamente el pie de anuncio común (CarMarketingController@show).
 *
 * Nota: los demás sitios (folletos, contrato) siguen con sus propios valores
 * hardcodeados por ahora — no se han tocado para no introducir cambios fuera
 * del alcance pedido. Candidato a consolidar en el futuro.
 */

return [

    'nombre' => 'JJ Import Motors',

    'telefono_1' => '675 70 14 39',
    'telefono_2' => '691 48 59 27',
    'email' => 'jjimportmotors@gmail.com',
    'web' => 'jjimportmotors.com',

    // Honorarios fijos del servicio de importacion (visible en el folleto).
    'precio_honorarios' => '1.500 €',

];
