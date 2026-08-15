<?php

namespace App\Support;

use App\Models\CarDocument;

/**
 * Expediente fijo de 17 documentos por coche.
 *
 * Claves alineadas con las del panel HTML (JJ_Panel_Coches.html).
 * Grupos:
 *   - seller_origin: vendedor/país de origen
 *   - purchase_transport: compra y traslado
 *   - spain_procedures: trámites en España
 */
class CarDocumentDefinitions
{
    private const DEFS = [
        // seller_origin (5)
        ['key' => 'kaufvertrag',         'name' => 'Contrato de compraventa (Kaufvertrag)',          'group' => CarDocument::GROUP_SELLER_ORIGIN, 'doc_type' => 'contract'],
        ['key' => 'coc',                 'name' => 'COC (Certificado de Conformidad)',               'group' => CarDocument::GROUP_SELLER_ORIGIN, 'doc_type' => 'permit'],
        ['key' => 'fahrzeugbrief',       'name' => 'Fahrzeugbrief / Teil 2 (ficha técnica)',         'group' => CarDocument::GROUP_SELLER_ORIGIN, 'doc_type' => 'permit'],
        ['key' => 'fahrzeugschein',      'name' => 'Fahrzeugschein / Teil 1 (permiso circulación)',  'group' => CarDocument::GROUP_SELLER_ORIGIN, 'doc_type' => 'permit'],
        ['key' => 'scheckheft',          'name' => 'Historial de mantenimiento (Scheckheft)',       'group' => CarDocument::GROUP_SELLER_ORIGIN, 'doc_type' => 'other'],

        // purchase_transport (3)
        ['key' => 'payment_proof',       'name' => 'Justificante de pago al vendedor',              'group' => CarDocument::GROUP_PURCHASE_TRANSPORT, 'doc_type' => 'invoice'],
        ['key' => 'transport_contract',  'name' => 'Contrato de transporte',                        'group' => CarDocument::GROUP_PURCHASE_TRANSPORT, 'doc_type' => 'contract'],
        ['key' => 'transport_invoice',   'name' => 'Factura del transporte',                        'group' => CarDocument::GROUP_PURCHASE_TRANSPORT, 'doc_type' => 'invoice'],

        // spain_procedures (9)
        ['key' => 'itv_import',          'name' => 'ITV de importación favorable',                  'group' => CarDocument::GROUP_SPAIN_PROCEDURES, 'doc_type' => 'permit'],
        ['key' => 'ficha_tecnica_es',    'name' => 'Ficha técnica española',                        'group' => CarDocument::GROUP_SPAIN_PROCEDURES, 'doc_type' => 'permit'],
        ['key' => 'iedmt_576',           'name' => 'IEDMT liquidado (mod. 576)',                    'group' => CarDocument::GROUP_SPAIN_PROCEDURES, 'doc_type' => 'invoice'],
        ['key' => 'ivtm',                'name' => 'IVTM (impuesto de circulación)',                'group' => CarDocument::GROUP_SPAIN_PROCEDURES, 'doc_type' => 'invoice'],
        ['key' => 'permiso_circulacion', 'name' => 'Permiso de circulación a nombre del cliente',   'group' => CarDocument::GROUP_SPAIN_PROCEDURES, 'doc_type' => 'registration'],
        ['key' => 'cliente_dni',         'name' => 'DNI/NIF del cliente',                           'group' => CarDocument::GROUP_SPAIN_PROCEDURES, 'doc_type' => 'other'],
        ['key' => 'cliente_contrato',    'name' => 'Contrato de encargo firmado',                   'group' => CarDocument::GROUP_SPAIN_PROCEDURES, 'doc_type' => 'contract'],
        ['key' => 'senal_recibo',        'name' => 'Recibo de señal',                               'group' => CarDocument::GROUP_SPAIN_PROCEDURES, 'doc_type' => 'invoice'],
        ['key' => 'seguro',              'name' => 'Seguro de transporte / importación',            'group' => CarDocument::GROUP_SPAIN_PROCEDURES, 'doc_type' => 'insurance'],

        // ai_reports (3) — PDFs de investigación que genera CLAUDE en el Desktop
        ['key' => 'informe_busqueda',    'name' => 'Informe de búsqueda (Claude)',                  'group' => CarDocument::GROUP_AI_REPORTS, 'doc_type' => 'pdf'],
        ['key' => 'informe_unidad',      'name' => 'Informe de unidad (Claude)',                    'group' => CarDocument::GROUP_AI_REPORTS, 'doc_type' => 'pdf'],
        ['key' => 'briefing_pdf',        'name' => 'Briefing / resumen cliente (Claude)',           'group' => CarDocument::GROUP_AI_REPORTS, 'doc_type' => 'pdf'],
    ];

    /**
     * Mapea doc_key → milestone del checklist que se marca al recibirlo.
     */
    public const DOC_TO_MILESTONE = [
        'coc' => 'coc_ordered',
        'itv_import' => 'itv_passed',
        'iedmt_576' => 'iedmt_paid',
        'permiso_circulacion' => 'registered',
    ];

    /**
     * @return array<int, array{key:string,name:string,group:string,doc_type:string}>
     */
    public function all(): array
    {
        return self::DEFS;
    }

    public function totalCount(): int
    {
        return count(self::DEFS);
    }
}
