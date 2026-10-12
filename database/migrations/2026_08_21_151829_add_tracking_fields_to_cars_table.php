<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Campos para el tracking público del proceso de importación.
     * El admin genera el token y comparte la URL con el cliente.
     */
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->string('tracking_token', 64)->nullable()->unique()->after('client_id');
            $table->timestamp('tracking_shared_at')->nullable()->after('tracking_token');
            $table->string('tracking_shared_with_email')->nullable()->after('tracking_shared_at');
            $table->timestamp('tracking_revoked_at')->nullable()->after('tracking_shared_with_email');
            $table->unsignedInteger('tracking_views')->default(0)->after('tracking_revoked_at');
            $table->date('expected_delivery_date')->nullable()->after('tracking_views');
        });
    }

    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn([
                'tracking_token',
                'tracking_shared_at',
                'tracking_shared_with_email',
                'tracking_revoked_at',
                'tracking_views',
                'expected_delivery_date',
            ]);
        });
    }
};
