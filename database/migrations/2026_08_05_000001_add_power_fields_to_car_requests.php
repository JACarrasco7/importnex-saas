<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('car_requests', function (Blueprint $table) {
            // Power preferences
            $table->integer('power_min')->nullable()->after('mileage_max');
            $table->integer('power_max')->nullable()->after('power_min');
            $table->string('engine_type')->nullable()->after('power_max');
        });
    }

    public function down(): void
    {
        Schema::table('car_requests', function (Blueprint $table) {
            $table->dropColumn(['power_min', 'power_max', 'engine_type']);
        });
    }
};
