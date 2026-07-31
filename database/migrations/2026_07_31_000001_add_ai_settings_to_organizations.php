<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-organization AI settings. Each tenant picks its own provider
     * (anthropic/openai/mistral/gemini/deepseek/minimax/glm), pastes its
     * API key (stored encrypted) and optionally overrides the default model.
     */
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('ai_provider', 32)->nullable()->after('subscribed_at');
            $table->string('ai_model', 128)->nullable()->after('ai_provider');
            $table->text('ai_api_key')->nullable()->after('ai_model');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['ai_provider', 'ai_model', 'ai_api_key']);
        });
    }
};
