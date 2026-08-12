<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // §3.4 — Mapeo de traccion del chat JSON a drivetrain en BD
        Schema::table('cars', function (Blueprint $table) {
            $table->string('drivetrain')->nullable()->after('transmission');
        });
    }

    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn('drivetrain');
        });
    }
};
