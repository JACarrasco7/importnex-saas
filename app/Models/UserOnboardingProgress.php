<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserOnboardingProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'organization_id',
        'step_organization_created',
        'step_first_vehicle_added',
        'step_team_invited',
        'step_plan_selected',
        'current_step',
        'completed_at',
        'skipped_at',
    ];

    protected $casts = [
        'step_organization_created' => 'boolean',
        'step_first_vehicle_added' => 'boolean',
        'step_team_invited' => 'boolean',
        'step_plan_selected' => 'boolean',
        'current_step' => 'integer',
        'completed_at' => 'datetime',
        'skipped_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function getProgressAttribute(): int
    {
        $completed = 0;
        if ($this->step_organization_created) $completed++;
        if ($this->step_first_vehicle_added) $completed++;
        if ($this->step_team_invited) $completed++;
        if ($this->step_plan_selected) $completed++;

        return (int) (($completed / 4) * 100);
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->current_step > 4 || $this->completed_at !== null;
    }

    public function getCanAdvanceAttribute(): bool
    {
        // Step 1: organization
        if ($this->current_step === 1 && !$this->step_organization_created) {
            return false;
        }

        // Step 2: first vehicle
        if ($this->current_step === 2 && !$this->step_first_vehicle_added) {
            return false;
        }

        // Step 3: team invite
        if ($this->current_step === 3 && !$this->step_team_invited) {
            return false;
        }

        // Step 4: plan selected (optional)
        return true;
    }

    public function advanceTo(int $step): bool
    {
        if ($step < 1 || $step > 4) {
            return false;
        }

        $this->current_step = $step;

        if ($step > 4) {
            $this->completed_at = now();
        }

        return $this->save();
    }

    public function completeStepOrganization(): bool
    {
        $this->step_organization_created = true;

        if (!$this->organization_id) {
            $this->organization_id = auth()->user()->organization_id;
        }

        return $this->save();
    }

    public function completeStepFirstVehicle(): bool
    {
        $this->step_first_vehicle_added = true;

        if (!$this->organization_id) {
            $this->organization_id = auth()->user()->organization_id;
        }

        return $this->save();
    }

    public function completeStepTeamInvite(): bool
    {
        $this->step_team_invited = true;
        return $this->save();
    }

    public function completeStepPlan(): bool
    {
        $this->step_plan_selected = true;

        if (!$this->organization_id) {
            $this->organization_id = auth()->user()->organization_id;
        }

        return $this->save();
    }

    public function skip(): void
    {
        $this->skipped_at = now();
        $this->completed_at = now();
        $this->current_step = 99; // Mark as skipped
        $this->save();
    }
}