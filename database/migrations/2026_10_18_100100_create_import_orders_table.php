<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Encargo de importación: une cliente + coche + solicitud + contrato + pagos.
 * Es la entidad central del flujo (ver docs de auditoría 18-oct-2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('car_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('car_request_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();

            $table->string('reference', 32)->unique();
            $table->string('state', 32)->default('draft');
            $table->timestamp('state_changed_at')->nullable();

            // Honorarios del servicio (lo que firma el cliente). 0 = cortesía.
            $table->decimal('fee_amount', 10, 2)->default(0);

            // Enlace del portal del cliente: hash para buscar, cifrado para
            // poder volver a mostrar/copiar el enlace en el panel.
            $table->string('portal_token_hash', 64)->nullable()->unique();
            $table->text('portal_token')->nullable();
            $table->timestamp('portal_token_rotated_at')->nullable();
            $table->timestamp('portal_last_seen_at')->nullable();
            $table->unsignedInteger('portal_views')->default(0);

            $table->date('expected_delivery_date')->nullable();
            $table->timestamp('cancellation_requested_at')->nullable();
            $table->text('cancellation_request_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('internal_notes')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['organization_id', 'state']);
            $table->index(['car_id', 'state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_orders');
    }
};
