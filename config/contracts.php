<?php

/*
 * Plantilla de contrato de prestación de servicios de importación de vehículos.
 *
 * ⚠️  BORRADOR — NO USAR EN PRODUCCIÓN SIN REVISIÓN DE LETRADO.
 *
 * Las claves de este archivo alimentan `resources/views/jj-import/contrato.blade.php`
 * (PDF final que recibe el cliente). Cualquier texto modificado aquí se refleja
 * automáticamente en los contratos futuros, pero los YA firmados conservan el
 * hash sha256 registrado en `contract_acceptances` para integridad legal.
 *
 * Compliance mínimo (España):
 *   - LSSI CE (Ley 34/2002) art. 9: información previa + identificación prestador.
 *   - RGPD: ver `config/legal.php` (en construcción).
 *   - LGDCU: derecho de desistimiento (aplica con excepciones en este servicio).
 *
 * Mantenimiento:
 *   1. Editar textos aquí.
 *   2. Comunicar cambio al abogado.
 *   3. Cualquier modificación se versiona en GIT (los aceptados previamente
 *      siguen vinculando al texto original por hash).
 */

return [

    'version' => '2026-08-21.0',

    'prestador' => [
        'razon_social' => 'JJ Import Motors',
        'cif' => '— pendiente de asignar —',
        'direccion' => 'Huelva, España',
        'email' => 'jjimportmotors@gmail.com',
        'telefono' => '+34 675 70 14 39',
        'registro_mercantil' => '— pendiente —',
    ],

    /*
     |--------------------------------------------------------------------------
     | Cláusulas del contrato
     |--------------------------------------------------------------------------
     | Cada elemento es una sección numerada del PDF. `placeholder` admite:
     |   :cliente_nombre, :cliente_email, :cliente_dni,
     |   :vehiculo_marca, :vehiculo_modelo, :vehiculo_anio,
     |   :vehiculo_vin, :precio_total, :honorarios, :fecha_firma, :contrato_id
     |
     | 'requiere_checkbox' = true fuerza al cliente a marcar casilla ANTES de firmar.
     */
    'clausulas' => [

        1 => [
            'titulo' => 'Objeto del contrato',
            'requiere_checkbox' => true,
            'cuerpo' => <<<'TXT'
JJ Import Motors (en adelante, "el Prestador") prestará al CLIENTE
(:cliente_nombre, :cliente_dni) el servicio de búsqueda, gestión de compra,
transporte, homologación y matriculación en España del vehículo marca
:vehiculo_marca modelo :vehiculo_modelo (año :vehiculo_anio), en adelante
"el Vehículo", por cuenta y encargo del CLIENTE.
TXT,
        ],

        2 => [
            'titulo' => 'Precio y forma de pago',
            'requiere_checkbox' => true,
            'cuerpo' => <<<'TXT'
Los honorarios del Prestador ascienden a :honorarios €, IVA incluido, pagaderos
en dos plazos: 50% a la firma del presente contrato y 50% a la entrega del
Vehículo en territorio español. El precio de compra del Vehículo
(:precio_total €) será satisfecho directamente por el CLIENTE al vendedor
en Alemania o al Prestador según se acuerde; en ningún caso JJ Import Motors
será propietario del Vehículo.
TXT,
        ],

        3 => [
            'titulo' => 'Obligaciones del Prestador',
            'requiere_checkbox' => true,
            'cuerpo' => <<<'TXT'
El Prestador se obliga a (i) buscar el Vehículo conforme a los criterios
comunicados por el CLIENTE; (ii) coordinar el transporte desde Alemania
hasta la dirección indicada por el CLIENTE; (iii) gestionar la obtención del
Certificado de Conformidad (COC) ante el fabricante; (iv) superar la ITV
de importación; (v) liquidar el impuesto de matriculación (IEDMT); y
(vi)matricular el Vehículo a nombre del CLIENTE en la DGT.
TXT,
        ],

        4 => [
            'titulo' => 'Obligaciones del CLIENTE',
            'requiere_checkbox' => true,
            'cuerpo' => <<<'TXT'
El CLIENTE se obliga a facilitar documentación veraz (DNI/NIE, justificante
de domicilio, empadronamiento) y a firmar cuantos documentos sean necesarios
para la matriculación. El incumplimiento documental podrá suspender los
plazos del Prestador.
TXT,
        ],

        5 => [
            'titulo' => 'Plazos orientativos',
            'requiere_checkbox' => false,
            'cuerpo' => <<<'TXT'
El plazo total estimado es de 6 a 10 semanas desde la firma, dependiendo de
la disponibilidad de transporte, la respuesta del fabricante para el COC y
la carga de trabajo de la ITV. Los plazos son orientativos y no constituyen
obligación esencial del contrato.
TXT,
        ],

        6 => [
            'titulo' => 'Derecho de desistimiento',
            'requiere_checkbox' => true,
            'cuerpo' => <<<'TXT'
Conforme al art. 103.m de la LGDCU, el CLIENTE NO podrá desistir del contrato
una vez el Prestador haya iniciado la búsqueda activa del Vehículo o realizado
gestiones preparatorias (contacto con vendedores alemanes, reserva de transporte,
solicitud de COC), al ser servicios personalizados.
TXT,
        ],

        7 => [
            'titulo' => 'Garantía y responsabilidades',
            'requiere_checkbox' => true,
            'cuerpo' => <<<'TXT'
El Prestador actúa como intermediario en la compra del Vehículo. La garantía
mecánica del Vehículo es la ofrecida por el fabricante o vendedor alemán,
sin responsabilidad adicional del Prestador más allá de la correcta gestión
del proceso administrativo. La garantía legal de conformidad se extingue por
el uso previo del Vehículo en Alemania.
TXT,
        ],

        8 => [
            'titulo' => 'Protección de datos',
            'requiere_checkbox' => true,
            'cuerpo' => <<<'TXT'
Los datos personales facilitados serán tratados por JJ Import Motors con la
finalidad de gestionar el presente contrato y las comunicaciones derivadas.
El CLIENTE puede ejercer sus derechos RGPD (acceso, rectificación, supresión,
oposición, portabilidad, limitación) escribiendo a :email. Conservación:
durante la relación contractual y 5 años adicionales para cumplimiento de
obligaciones legales.
TXT,
        ],

        9 => [
            'titulo' => 'Ley aplicable y jurisdicción',
            'requiere_checkbox' => false,
            'cuerpo' => <<<'TXT'
El presente contrato se rige por la legislación española. Para cualquier
controversia, las partes se someten a los Juzgados y Tribunales de Huelva,
salvo normas imperativas de protección al consumidor.
TXT,
        ],

        10 => [
            'titulo' => 'Aceptación y firma electrónica',
            'requiere_checkbox' => true,
            'cuerpo' => <<<'TXT'
El CLIENTE declara haber leído, comprendido y aceptado todas las cláusulas
anteriores. La firma se realiza mediante click en el botón "Aceptar y firmar"
de la página web del Prestador, con registro de fecha, hora, dirección IP y
navegador, conforme al art. 9 de la LSSI-CE y art. 25 del Reglamento eIDAS.
TXT,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Mensajes de la vista pública del contrato
    |--------------------------------------------------------------------------
    */
    'ui' => [
        'titulo' => 'Contrato de prestación de servicios',
        'subtitulo' => 'Servicio de importación de vehículo desde Alemania',
        'checkbox_label' => 'He leído y acepto las {n} cláusulas del contrato, incluyendo desistimiento, datos personales y jurisdicción.',
        'boton_aceptar' => 'Aceptar y firmar electrónicamente',
        'leyendo' => 'Generando documento firmado...',
        'gracias_titulo' => 'Contrato firmado correctamente',
        'gracias_texto' => 'Hemos registrado tu aceptación. Puedes descargar el PDF firmado a continuación.',
        'descargar_pdf' => 'Descargar contrato firmado (PDF)',
        'legal_notice' => 'Documento firmado electrónicamente. Conforme a LSSI-CE art. 9 y eIDAS art. 25.',
        'errores' => [
            'token_invalido' => 'Este enlace de contrato no es válido o ha caducado.',
            'ya_firmado' => 'Este contrato ya ha sido firmado.',
            'no_acepta' => 'Debes marcar la casilla de aceptación antes de continuar.',
        ],
    ],
];
