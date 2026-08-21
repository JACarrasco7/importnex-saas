<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_acceptances', function (Blueprint $table) {
            $table->id();

            // car_id con restrict: los contratos firmados son archivo legal y
            // nunca deben borrarse en cascada (aunque el coche se purge).
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('car_id')->constrained()->restrictOnDelete();

            // Identificador público único del contrato (token URL-safe).
            $table->string('public_token', 64)->unique();

            // Versión del texto del contrato que se firmó (config/contracts.php).
            $table->string('contract_version', 32);

            // Hash sha256 del texto COMPLETO del contrato en el momento de la firma.
            // Garantiza integridad legal: el cliente firmó EXACTAMENTE este texto.
            $table->string('contract_hash', 64);

            // Snapshot de los datos del cliente (pueden cambiar tras la firma).
            $table->json('snapshot');

            $table->string('client_email', 191);
            $table->string('client_name', 191)->nullable();
            $table->string('client_dni', 32)->nullable();

            $table->timestamp('accepted_at')->nullable();
            $table->ipAddress('accepted_ip')->nullable();
            $table->string('user_agent', 191)->nullable();
            $table->string('locale', 8)->default('es');

            $table->timestamps();

            $table->index(['car_id', 'accepted_at']);
            $table->index(['organization_id', 'accepted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_acceptances');
    }
};
