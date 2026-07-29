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
        Schema::table('car_photos', function (Blueprint $table) {
            $table->unsignedBigInteger('organization_id')->nullable()->after('car_id');
        });

        Schema::table('car_documents', function (Blueprint $table) {
            $table->unsignedBigInteger('organization_id')->nullable()->after('car_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('car_photos', function (Blueprint $table) {
            $table->dropColumn('organization_id');
        });

        Schema::table('car_documents', function (Blueprint $table) {
            $table->dropColumn('organization_id');
        });
    }
};
