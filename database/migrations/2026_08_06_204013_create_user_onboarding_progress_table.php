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
        Schema::create('user_onboarding_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();

            // 4 pasos del wizard
            $table->boolean('step_organization_created')->default(false);
            $table->boolean('step_first_vehicle_added')->default(false);
            $table->boolean('step_team_invited')->default(false);
            $table->boolean('step_plan_selected')->default(false);

            // Meta
            $table->integer('current_step')->default(1); // 1-4
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('skipped_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'organization_id']);
            $table->index('current_step');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_onboarding_progress');
    }
};
