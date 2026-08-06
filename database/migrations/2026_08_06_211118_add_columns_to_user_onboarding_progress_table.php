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
        $hasUserId = Schema::hasColumn('user_onboarding_progress', 'user_id');

        if (! $hasUserId) {
            Schema::table('user_onboarding_progress', function (Blueprint $table) {
                $table->foreignId('user_id')->after('id')->constrained()->onDelete('cascade');
                $table->foreignId('organization_id')->nullable()->after('user_id')->constrained()->onDelete('cascade');
                $table->boolean('step_organization_created')->default(false);
                $table->boolean('step_first_vehicle_added')->default(false);
                $table->boolean('step_team_invited')->default(false);
                $table->boolean('step_plan_selected')->default(false);
                $table->unsignedTinyInteger('current_step')->default(1);
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('skipped_at')->nullable();

                $table->index(['user_id', 'current_step']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('user_onboarding_progress', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['organization_id']);
            $table->dropIndex(['user_id', 'current_step']);
            $table->dropColumn([
                'user_id',
                'organization_id',
                'step_organization_created',
                'step_first_vehicle_added',
                'step_team_invited',
                'step_plan_selected',
                'current_step',
                'completed_at',
                'skipped_at',
            ]);
        });
    }
};
