<?php

namespace App\Support;

use App\Models\CarChecklist;

/**
 * Definiciones fijas del checklist de coche:
 * - 6 hitos (milestone) del proceso
 * - 80 puntos de inspección agrupados en 8 secciones
 *
 * Mismas claves que el panel HTML (JJ_Panel_Coches.html) para no romper la migración.
 * 80 puntos (no 76) porque añadimos los 4 extra del bloque "Diagnosis OBD (escáner)"
 * y los 2 del bloque "Prueba y verificación" para alinear con las prácticas reales.
 */
class CarChecklistDefinitions
{
    /**
     * 6 hitos del proceso.
     */
    private const MILESTONES = [
        ['key' => 'deposit_paid',           'name' => 'Depósito pagado'],
        ['key' => 'transport_contracted',   'name' => 'Transporte contratado'],
        ['key' => 'coc_ordered',            'name' => 'COC pedido'],
        ['key' => 'itv_passed',             'name' => 'ITV hecha'],
        ['key' => 'iedmt_paid',             'name' => 'IEDMT pagado'],
        ['key' => 'registered',             'name' => 'Matriculado'],
    ];

    /**
     * 76 puntos de inspección agrupados en 6 secciones.
     * Prioridades: critical | important | minor
     */
    private const INSPECTIONS = [
        'Exterior — carrocería y pintura' => [
            ['key' => 'ext_hood',           'name' => 'Capó: alineación y estado',                                  'priority' => 'critical'],
            ['key' => 'ext_roof',           'name' => 'Techo: sin abolladuras ni granizo',                          'priority' => 'important'],
            ['key' => 'ext_quarter_l',      'name' => 'Aleta delantera izq.: espesor pintura (espesímetro) y alineación', 'priority' => 'critical'],
            ['key' => 'ext_quarter_r',      'name' => 'Aleta delantera der.: espesor pintura (espesímetro) y alineación', 'priority' => 'critical'],
            ['key' => 'ext_door_fl',        'name' => 'Puerta del. izq.: espesor pintura y alineación',             'priority' => 'critical'],
            ['key' => 'ext_door_rl',        'name' => 'Puerta tras. izq.: espesor pintura y alineación',            'priority' => 'critical'],
            ['key' => 'ext_door_fr',        'name' => 'Puerta del. der.: espesor pintura y alineación',             'priority' => 'critical'],
            ['key' => 'ext_door_rr',        'name' => 'Puerta tras. der.: espesor pintura y alineación',            'priority' => 'critical'],
            ['key' => 'ext_trunk',          'name' => 'Maletero/portón: espesor pintura, alineación y junta',       'priority' => 'critical'],
            ['key' => 'ext_bumper_f',       'name' => 'Parachoques delantero: grapas intactas, sin fisuras',        'priority' => 'important'],
            ['key' => 'ext_bumper_r',       'name' => 'Parachoques trasero: grapas intactas, sin fisuras',         'priority' => 'important'],
            ['key' => 'ext_lights',         'name' => 'Faros y ópticas: sin condensación, todas luces OK',         'priority' => 'critical'],
            ['key' => 'ext_mirrors',        'name' => 'Retrovisores: cristal sin fisuras, plegado eléctrico OK',    'priority' => 'minor'],
            ['key' => 'ext_paint_compare',  'name' => 'Comparar espesores entre paneles (repintados = accidente)',  'priority' => 'critical'],
        ],
        'Neumáticos y llantas' => [
            ['key' => 'tire_fl',            'name' => 'Delantero izq.: profundidad dibujo y fecha DOT',             'priority' => 'important'],
            ['key' => 'tire_fr',            'name' => 'Delantero der.: profundidad dibujo y fecha DOT',             'priority' => 'important'],
            ['key' => 'tire_rl',            'name' => 'Trasero izq.: profundidad dibujo y fecha DOT',               'priority' => 'important'],
            ['key' => 'tire_rr',            'name' => 'Trasero der.: profundidad dibujo y fecha DOT',               'priority' => 'important'],
            ['key' => 'tire_spare',         'name' => 'Rueda de repuesto / kit antipinchazos presente',              'priority' => 'minor'],
            ['key' => 'tire_brand_match',   'name' => 'Las 4 marcas y modelos coinciden (o anotar por qué no)',     'priority' => 'important'],
            ['key' => 'wheel_alloy',        'name' => 'Llantas: sin golpes ni fisuras, tuercas completas',          'priority' => 'minor'],
            ['key' => 'tire_pressure',      'name' => 'Presión de los 4 neumáticos correcta',                        'priority' => 'minor'],
        ],
        'Lunas y cristales' => [
            ['key' => 'glass_windshield',   'name' => 'Parabrisas: código fecha grabado, sin fisuras ni impactos',  'priority' => 'critical'],
            ['key' => 'glass_rear',         'name' => 'Luna trasera: fecha coherente con año, resistencias OK',     'priority' => 'important'],
            ['key' => 'glass_window_fl',    'name' => 'Ventanilla delantera izq.: fecha fabricación y estado',      'priority' => 'minor'],
            ['key' => 'glass_window_fr',    'name' => 'Ventanilla delantera der.: fecha fabricación y estado',      'priority' => 'minor'],
            ['key' => 'glass_window_rl',    'name' => 'Ventanilla trasera izq.: fecha fabricación y estado',        'priority' => 'minor'],
            ['key' => 'glass_window_rr',    'name' => 'Ventanilla trasera der.: fecha fabricación y estado',        'priority' => 'minor'],
            ['key' => 'glass_sunroof',      'name' => 'Techo solar/panorámico (si tiene): estanqueidad y mecanismo','priority' => 'minor'],
            ['key' => 'glass_dates_match',  'name' => 'Todas las fechas de cristales coinciden entre sí',            'priority' => 'critical'],
        ],
        'Óxido, bajos y estructura' => [
            ['key' => 'rust_underbody_f',   'name' => 'Bajos delanteros: sin óxido ni golpes',                      'priority' => 'critical'],
            ['key' => 'rust_underbody_r',   'name' => 'Bajos traseros: sin óxido ni golpes',                        'priority' => 'critical'],
            ['key' => 'rust_wheel_arches',  'name' => 'Pasos de rueda (los 4): sin óxido perforante',               'priority' => 'critical'],
            ['key' => 'rust_door_frames',   'name' => 'Marcos de las 4 puertas: sin óxido',                         'priority' => 'critical'],
            ['key' => 'rust_sills',         'name' => 'Umbrales: sin óxido ni deformación',                         'priority' => 'critical'],
            ['key' => 'rust_roof_channel',  'name' => 'Canal de agua del techo: sin óxido',                         'priority' => 'important'],
            ['key' => 'rust_trunk_floor',   'name' => 'Suelo del maletero: sin óxido ni humedad',                   'priority' => 'important'],
            ['key' => 'rust_jack_points',   'name' => 'Puntos de anclaje del gato: intactos',                       'priority' => 'minor'],
            ['key' => 'rust_exhaust',       'name' => 'Escape: sin fugas ni óxido perforante',                     'priority' => 'important'],
            ['key' => 'rust_welds',         'name' => 'Soldaduras/chasis: sin señales de reparación estructural',  'priority' => 'critical'],
        ],
        'Interior' => [
            ['key' => 'int_seats',          'name' => 'Tapicería asientos: sin manchas, quemaduras ni roturas',      'priority' => 'important'],
            ['key' => 'int_wheel_gear',     'name' => 'Volante y pomo de cambio: desgaste coherente con km',        'priority' => 'minor'],
            ['key' => 'int_pedals',         'name' => 'Pedales: desgaste coherente con los km',                     'priority' => 'minor'],
            ['key' => 'int_headliner',      'name' => 'Techo interior (guarnecido): sin manchas de humedad',        'priority' => 'minor'],
            ['key' => 'int_carpets',        'name' => 'Moqueta/suelo: sin humedad ni olores',                       'priority' => 'minor'],
            ['key' => 'int_airbags',        'name' => 'Airbags: testigo apagado, sin señales de despliegue',        'priority' => 'critical'],
            ['key' => 'int_seatbelts',      'name' => 'Cinturones: funcionan bien, sin deshilachados',              'priority' => 'critical'],
            ['key' => 'int_climate',        'name' => 'Climatizador: frío y calor reales en todas las salidas',    'priority' => 'important'],
            ['key' => 'int_windows_mirror', 'name' => 'Elevalunas y espejos eléctricos: todos funcionan',          'priority' => 'minor'],
            ['key' => 'int_infotainment',   'name' => 'Pantalla/infoentretenimiento: enciende, sin píxeles muertos','priority' => 'minor'],
            ['key' => 'int_trunk',          'name' => 'Maletero: suelo, forros y rueda de repuesto/kit',           'priority' => 'minor'],
            ['key' => 'int_odors',          'name' => 'Olores: sin humedad, tabaco ni combustible',                 'priority' => 'important'],
        ],
        'Motor y mecánica' => [
            ['key' => 'mec_cold_start',     'name' => 'Arranque en frío: sin ruidos/humos/testigos',                'priority' => 'critical'],
            ['key' => 'mec_idle',           'name' => 'Ralentí estable',                                             'priority' => 'important'],
            ['key' => 'mec_oil',            'name' => 'Nivel y estado del aceite (color, olor, nivel)',             'priority' => 'critical'],
            ['key' => 'mec_coolant',        'name' => 'Nivel y estado del refrigerante (sin aceite mezclado)',     'priority' => 'critical'],
            ['key' => 'mec_oil_leaks',      'name' => 'Fugas de aceite (culata, cárter, tapa válvulas)',            'priority' => 'critical'],
            ['key' => 'mec_fluid_leaks',    'name' => 'Fugas de refrigerante o dirección asistida',                 'priority' => 'critical'],
            ['key' => 'mec_belt',           'name' => 'Correa de accesorios/distribución: estado y ruido',          'priority' => 'important'],
            ['key' => 'mec_turbo',          'name' => 'Turbo: sin holgura en el eje, sin humo azul/negro',          'priority' => 'critical'],
            ['key' => 'mec_battery',        'name' => 'Batería 12V: fecha fabricación y estado de carga',           'priority' => 'important'],
            ['key' => 'mec_engine_mounts',  'name' => 'Soportes de motor: sin holguras',                            'priority' => 'minor'],
            ['key' => 'mec_brakes',         'name' => 'Frenos: discos y pastillas, sin vibración al frenar',         'priority' => 'critical'],
            ['key' => 'mec_suspension',     'name' => 'Suspensión: sin ruidos en badenes, sin fugas amortiguadores','priority' => 'important'],
            ['key' => 'mec_clutch',         'name' => 'Embrague: recorrido y agarre correctos (si manual)',         'priority' => 'important'],
            ['key' => 'mec_auto_gearbox',   'name' => 'Cambio automático: cambios suaves, sin tirones',             'priority' => 'important'],
            ['key' => 'mec_egr_dpf',        'name' => 'EGR/DPF (diésel): sin síntomas de obstrucción',              'priority' => 'important'],
        ],
        'Diagnosis OBD (escáner)' => [
            ['key' => 'obd_current_codes',  'name' => 'Códigos de error actuales (todas las ECUs)',                 'priority' => 'critical'],
            ['key' => 'obd_memory_codes',   'name' => 'Códigos de error en memoria / borrados recientemente',       'priority' => 'important'],
            ['key' => 'obd_km_match',       'name' => 'Kilometraje en las distintas ECUs coincide con el cuadro',    'priority' => 'critical'],
            ['key' => 'obd_live_data',      'name' => 'Datos en vivo: temperatura motor, RPM ralentí, sondas O2',   'priority' => 'important'],
            ['key' => 'obd_actuator_test',  'name' => 'Test de actuadores (si aplica)',                             'priority' => 'minor'],
            ['key' => 'obd_ecu_sw',         'name' => 'Versión de software de la ECU / actualizaciones pendientes', 'priority' => 'minor'],
        ],
        'Prueba y verificación' => [
            ['key' => 'ver_road_test',      'name' => 'Prueba en carretera (ciudad y carretera)',                   'priority' => 'critical'],
            ['key' => 'ver_vin_match',      'name' => 'VIN coincide (chasis, parabrisas, papeles, etiqueta puerta)','priority' => 'critical'],
            ['key' => 'ver_km_consistent',  'name' => 'Kilometraje coherente con el desgaste general',               'priority' => 'important'],
            ['key' => 'ver_service_book',   'name' => 'Libro de mantenimiento (Scheckheft) revisado',               'priority' => 'important'],
            ['key' => 'ver_recalls',        'name' => 'Recalls pendientes (por VIN)',                               'priority' => 'critical'],
            ['key' => 'ver_defect_photos',  'name' => 'Fotos de todos los defectos encontrados',                     'priority' => 'minor'],
            ['key' => 'ver_second_key',     'name' => 'Segunda llave y manual del propietario presentes',            'priority' => 'minor'],
        ],
    ];

    /**
     * @return array<int, array{key:string,name:string,kind:string,priority:?string,section:?string}>
     */
    public function all(): array
    {
        $rows = [];
        foreach (self::MILESTONES as $m) {
            $rows[] = [
                'key' => $m['key'],
                'name' => $m['name'],
                'kind' => CarChecklist::KIND_MILESTONE,
                'priority' => null,
                'section' => null,
            ];
        }
        foreach (self::INSPECTIONS as $section => $items) {
            foreach ($items as $item) {
                $rows[] = [
                    'key' => $item['key'],
                    'name' => $item['name'],
                    'kind' => CarChecklist::KIND_INSPECTION,
                    'priority' => $item['priority'],
                    'section' => $section,
                ];
            }
        }
        return $rows;
    }

    public function milestones(): array
    {
        return self::MILESTONES;
    }

    public function inspections(): array
    {
        return self::INSPECTIONS;
    }

    public function totalCount(): int
    {
        return count(self::MILESTONES) + array_sum(array_map('count', self::INSPECTIONS));
    }
}
