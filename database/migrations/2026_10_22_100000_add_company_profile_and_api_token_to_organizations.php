<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SaaS: cada organización guarda sus datos de empresa (marca, CIF, contacto,
 * IBAN) y su propio token de API. Hasta ahora estaban fijos en el código.
 *
 * Migración de datos (una sola vez): la organización propietaria (is_owner)
 * recibe los datos que hasta hoy estaban fijos en el código, para que su web
 * y sus contratos sigan igual tras el despliegue. Lo que haya en .env manda.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->json('company')->nullable()->after('logo');
            $table->string('api_token_hash', 64)->nullable()->unique()->after('company');
        });

        $profile = array_filter([
            'legal_name' => env('CONTRACT_PRESTADOR_RAZON_SOCIAL'),
            'tax_id' => env('CONTRACT_PRESTADOR_CIF'),
            'address' => env('CONTRACT_PRESTADOR_DIRECCION', 'Huelva, España'),
            'city' => 'Huelva',
            'phone' => env('COMPANY_PHONE', '675 70 14 39'),
            'phone_2' => env('COMPANY_PHONE_2', '691 48 59 27'),
            'email' => env('COMPANY_EMAIL', 'jjimportmotors@gmail.com'),
            'website' => env('COMPANY_WEB', 'jjimportmotors.com'),
            'iban' => env('CONTRACT_IBAN'),
            'iban_holder' => env('CONTRACT_IBAN_HOLDER'),
            'iban_bank' => env('CONTRACT_BANK_NAME'),
        ], fn ($v) => filled($v));

        // La organización operadora (la más antigua) no paga suscripción ni
        // tiene límites. Si nadie está marcada como tal, se marca la primera.
        if (! DB::table('organizations')->where('is_owner', true)->exists()) {
            $first = DB::table('organizations')->orderBy('id')->value('id');
            if ($first) {
                DB::table('organizations')->where('id', $first)->update(['is_owner' => true]);
            }
        }

        DB::table('organizations')
            ->where(fn ($q) => $q->where('is_owner', true)->orWhere('name', 'JJ Import Motors'))
            ->whereNull('company')
            ->update(['company' => json_encode($profile)]);

        DB::table('organizations')
            ->where('name', 'JJ Import Motors')
            ->whereNull('logo')
            ->update(['logo' => '/images/jj-import/logo-insignia.png']);
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropUnique(['api_token_hash']);
            $table->dropColumn(['company', 'api_token_hash']);
        });
    }
};
