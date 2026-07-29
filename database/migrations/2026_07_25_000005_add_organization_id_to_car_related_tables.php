<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds organization_id to car_expenses and car_checklists.
 * (client_contact_logs already received its column in the 000004 migration.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('car_expenses', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('car_id')->constrained()->onDelete('cascade');
            $table->index(['organization_id', 'car_id']);
        });

        Schema::table('car_checklists', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('car_id')->constrained()->onDelete('cascade');
            $table->index(['organization_id', 'car_id']);
        });
    }

    public function down(): void
    {
        Schema::table('car_expenses', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropColumn('organization_id');
        });

        Schema::table('car_checklists', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropColumn('organization_id');
        });
    }
};
