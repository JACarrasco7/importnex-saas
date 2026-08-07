<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            // N7: Webhook URL (Slack/Discord/Teams). Cifrado en reposo.
            $table->text('notification_webhook_url')->nullable()->after('ai_api_key');
            // N7: Tipos de alerta que disparan webhook. null/[] = todos.
            $table->json('notification_webhook_types')->nullable()->after('notification_webhook_url');
            // N8: Preferencias por tipo (true=activo, false=silenciado).
            $table->json('notification_preferences')->nullable()->after('notification_webhook_types');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['notification_webhook_url', 'notification_webhook_types', 'notification_preferences']);
        });
    }
};
