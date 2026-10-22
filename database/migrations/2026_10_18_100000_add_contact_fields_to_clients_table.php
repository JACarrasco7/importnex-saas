<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Campos de contacto estructurados en clients (antes solo `contact_info` libre).
 *
 * Necesarios para: enviar el contrato y su copia firmada por email, el código
 * de verificación (OTP) y los avisos del encargo; el botón de WhatsApp; y
 * precargar nombre/DNI en el contrato.
 *
 * Backfill SOLO rellena columnas nuevas vacías (no toca datos existentes):
 *   1. email/phone de la última solicitud (car_requests) vinculada al cliente.
 *   2. Si sigue vacío, extrae email/teléfono de contact_info.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('email', 191)->nullable()->after('contact_info');
            $table->string('phone', 50)->nullable()->after('email');
            // Cifrado con APP_KEY (cast `encrypted` en el modelo): texto largo.
            $table->text('dni')->nullable()->after('phone');
            $table->string('address', 255)->nullable()->after('dni');
            $table->string('preferred_channel', 16)->default('email')->after('address');
            $table->index('email');
        });

        DB::table('clients')
            ->select(['id', 'contact_info'])
            ->orderBy('id')
            ->chunkById(500, function ($clients) {
                foreach ($clients as $client) {
                    $request = DB::table('car_requests')
                        ->where('client_id', $client->id)
                        ->whereNull('deleted_at')
                        ->orderByDesc('id')
                        ->first(['email', 'phone']);

                    $email = $request->email ?? null;
                    $phone = $request->phone ?? null;
                    $raw = (string) ($client->contact_info ?? '');

                    if (! $email && preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $raw, $m)) {
                        $email = strtolower($m[0]);
                    }
                    if (! $phone && preg_match('/\+?\d[\d\s().-]{7,}\d/', $raw, $m)) {
                        $phone = trim($m[0]);
                    }

                    $updates = array_filter([
                        'email' => $email ? substr($email, 0, 191) : null,
                        'phone' => $phone ? substr($phone, 0, 50) : null,
                    ]);
                    if ($updates) {
                        DB::table('clients')->where('id', $client->id)->update($updates);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex(['email']);
            $table->dropColumn(['email', 'phone', 'dni', 'address', 'preferred_channel']);
        });
    }
};
