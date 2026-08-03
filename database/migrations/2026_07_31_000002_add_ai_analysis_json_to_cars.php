<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds a single `ai_analysis_json` column to cars.
     *
     * Stores the full raw AI response for verification suggestions so the
     * user can review proposed values per field via the modal on the Verify
     * page and pick which ones to apply. This keeps the original car fields
     * untouched until the user explicitly applies a suggestion.
     */
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->json('ai_analysis_json')->nullable()->after('schema_version');
        });
    }

    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn('ai_analysis_json');
        });
    }
};
