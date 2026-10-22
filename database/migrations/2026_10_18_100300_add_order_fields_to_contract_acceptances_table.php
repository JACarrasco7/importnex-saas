<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ciclo de vida completo del contrato: estado, caducidad, anulación, rechazo,
 * verificación OTP y PDF archivado una sola vez al firmar.
 *
 * Los contratos ya firmados NO se modifican salvo el backfill de `status`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_acceptances', function (Blueprint $table) {
            $table->foreignId('import_order_id')->nullable()->after('car_id')->constrained()->restrictOnDelete();
            $table->string('status', 16)->nullable()->after('contract_hash');
            $table->timestamp('expires_at')->nullable()->after('status');
            $table->timestamp('viewed_at')->nullable()->after('expires_at');
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason', 255)->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('otp_verified_at')->nullable();
            $table->string('otp_sent_to', 191)->nullable();
            $table->string('pdf_path', 500)->nullable();
            $table->string('pdf_hash', 64)->nullable();
            $table->json('signer_corrections')->nullable();
            $table->unsignedTinyInteger('reminders_sent')->default(0);
            $table->timestamp('last_reminder_at')->nullable();

            $table->index(['import_order_id', 'status']);
        });

        DB::table('contract_acceptances')->whereNotNull('accepted_at')->update(['status' => 'signed']);
        DB::table('contract_acceptances')->whereNull('accepted_at')->update(['status' => 'sent']);
    }

    public function down(): void
    {
        Schema::table('contract_acceptances', function (Blueprint $table) {
            $table->dropIndex(['import_order_id', 'status']);
            $table->dropConstrainedForeignId('import_order_id');
            $table->dropColumn([
                'status', 'expires_at', 'viewed_at', 'voided_at', 'void_reason', 'rejected_at',
                'rejection_reason', 'otp_verified_at', 'otp_sent_to', 'pdf_path', 'pdf_hash',
                'signer_corrections', 'reminders_sent', 'last_reminder_at',
            ]);
        });
    }
};
