<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MessageTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Initial contact German seller',
                'content' => 'Hallo, ich interessiere mich für Ihr Fahrzeug {{coche}}. Könnten Sie mir bitte folgende Informationen zusenden: Scheckheft, COC, Fotos von eventuellen Mängeln und den Ankaufpreis? Vielen Dank.',
                'language' => 'de',
                'category' => 'seller',
                'placeholders' => ['coche'],
            ],
            [
                'name' => 'Test drive request',
                'content' => 'Hallo, ich würde gerne das Fahrzeug {{coche}} probefahren. Wann wäre es Ihnen möglich? Ich bin in der Nähe von {{ciudad}}. Danke.',
                'language' => 'de',
                'category' => 'seller',
                'placeholders' => ['coche', 'ciudad'],
            ],
            [
                'name' => 'Spanish client offer',
                'content' => 'Hola {{nombre}}, te presento esta oportunidad: {{coche}} de {{year}}, {{mileage}} km, por {{precio}} € todo incluido (vehículo + importación + trámites). ¿Te interesa ver más detalles?',
                'language' => 'es',
                'category' => 'client',
                'placeholders' => ['nombre', 'coche', 'year', 'mileage', 'precio'],
            ],
            [
                'name' => 'Detailed quote',
                'content' => 'Hola {{nombre}}, aquí tienes el desglose para {{coche}}:\n\n- Vehicle: {{purchase_price}} €\n- Transport: {{transport}} €\n- ITV/Homologation: {{itv_fee}} €\n- IEDMT: {{iedmt}} €\n- Document management: {{coc_fee}} €\n- DGT fees: {{dgt_fees}} €\n- Professional fees: {{professional_fees}} €\n\nTOTAL: {{total}} €\n\nShall we proceed?',
                'language' => 'es',
                'category' => 'client',
                'placeholders' => ['nombre', 'coche', 'purchase_price', 'transport', 'itv_fee', 'iedmt', 'coc_fee', 'dgt_fees', 'professional_fees', 'total'],
            ],
            [
                'name' => 'Order confirmation',
                'content' => 'Hola {{nombre}}, confirmo que procedemos con la importación de {{coche}}. Para formalizar el encargo, por favor abona la señal de {{deposit}} €. Te enviaré el contrato para tu firma.',
                'language' => 'es',
                'category' => 'client',
                'placeholders' => ['nombre', 'coche', 'deposit'],
            ],
            [
                'name' => 'Delivery notice',
                'content' => 'Hola {{nombre}}, tu coche {{coche}} ya está listo para la entrega. ¿Prefieres recogerlo en {{location}} o te lo llevamos a tu dirección? Quedo a la espera.',
                'language' => 'es',
                'category' => 'client',
                'placeholders' => ['nombre', 'coche', 'location'],
            ],
        ];

        foreach ($templates as $template) {
            \App\Models\MessageTemplate::firstOrCreate(
                ['name' => $template['name']],
                $template
            );
        }
    }
}
