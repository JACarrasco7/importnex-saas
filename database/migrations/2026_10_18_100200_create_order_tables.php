<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tablas hijas del encargo: bitácora, mensajes, aprobaciones, pagos, media y
 * códigos de verificación (OTP) del portal del cliente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('import_order_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);
            $table->string('from_state', 32)->nullable();
            $table->string('to_state', 32)->nullable();
            // user | client | system
            $table->string('actor_type', 16)->default('system');
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 255);
            $table->text('body')->nullable();
            $table->boolean('visible_to_client')->default(false);
            $table->json('meta')->nullable();
            $table->ipAddress('ip')->nullable();
            $table->timestamps();

            $table->index(['import_order_id', 'created_at']);
        });

        Schema::create('order_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('import_order_id')->constrained()->cascadeOnDelete();
            // client | team
            $table->string('author_type', 16);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            // client = lo ve el cliente · internal = nota del equipo
            $table->string('visibility', 16)->default('client');
            $table->timestamp('read_by_client_at')->nullable();
            $table->timestamp('read_by_team_at')->nullable();
            $table->ipAddress('ip')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['import_order_id', 'created_at']);
        });

        Schema::create('order_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('import_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('kind', 24);
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('status', 16)->default('pending');
            $table->string('decision', 16)->nullable();
            $table->text('client_comment')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('reminded_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->ipAddress('resolved_ip')->nullable();
            $table->timestamp('otp_verified_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['import_order_id', 'status']);
        });

        Schema::create('order_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('import_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_approval_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 24);
            $table->string('concept', 255);
            $table->decimal('amount', 10, 2);
            $table->string('status', 16)->default('pending');
            $table->string('method', 16)->nullable();
            // Enlace de pago creado a mano en el panel de Stripe (Payment Link).
            $table->string('stripe_payment_link', 500)->nullable();
            $table->string('reference', 191)->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['import_order_id', 'status']);
        });

        Schema::create('order_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('import_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_approval_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            // inspection | transport | documents | delivery | other
            $table->string('collection', 24)->default('other');
            $table->string('disk', 16)->default('local');
            $table->string('path', 500);
            $table->string('original_name', 255)->nullable();
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('caption', 255)->nullable();
            // client | internal (por defecto interno: nada sale al portal sin querer)
            $table->string('visibility', 16)->default('internal');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['import_order_id', 'visibility']);
        });

        Schema::create('portal_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('import_order_id')->constrained()->cascadeOnDelete();
            // sign_contract | approve | cancel
            $table->string('purpose', 24);
            $table->string('subject_type', 40)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('code_hash', 64);
            $table->string('channel', 16)->default('email');
            $table->string('sent_to', 191);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->ipAddress('ip')->nullable();
            $table->timestamps();

            $table->index(['import_order_id', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_otps');
        Schema::dropIfExists('order_media');
        Schema::dropIfExists('order_payments');
        Schema::dropIfExists('order_approvals');
        Schema::dropIfExists('order_messages');
        Schema::dropIfExists('order_events');
    }
};
