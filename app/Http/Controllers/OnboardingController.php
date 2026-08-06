<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserOnboardingProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class OnboardingController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Si el usuario ya completó el onboarding, redirigir al dashboard
        $progress = $user->onboardingProgress;
        if ($progress && $progress->is_completed) {
            return redirect()->route('dashboard');
        }

        // Crear progreso si no existe
        if (!$progress) {
            $progress = UserOnboardingProgress::create([
                'user_id' => $user->id,
                'organization_id' => $user->organization_id,
                'current_step' => 1,
            ]);
        }

        // Datos del paso actual
        $stepData = $this->getStepData($progress->current_step, $user);

        return Inertia::render('Onboarding/Wizard', [
            'progress' => $progress->load('organization'),
            'stepData' => $stepData,
            'user' => $user,
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $progress = $user->onboardingProgress;

        if (!$progress) {
            return back()->with('error', 'No se encontró progreso de onboarding');
        }

        $step = $request->input('step');

        switch ($step) {
            case 1:
                // Organization created (ya existe tras signup)
                $progress->completeStepOrganization();
                $progress->advanceTo(2);
                break;

            case 2:
                // First vehicle added
                if ($user->organization?->cars()->count() > 0) {
                    $progress->completeStepFirstVehicle();
                    $progress->advanceTo(3);
                }
                break;

            case 3:
                // Team invited (marcar como completado aunque no haya invitaciones)
                $progress->completeStepTeamInvite();
                $progress->advanceTo(4);
                break;

            case 4:
                // Plan selected (marcar como completado)
                $progress->completeStepPlan();
                $progress->completed_at = now();
                $progress->current_step = 99;
                $progress->save();
                return redirect()->route('dashboard')->with('success', '¡Bienvenido! Has completado el onboarding.');

            case 'skip':
                // Saltar onboarding
                $progress->skip();
                return redirect()->route('dashboard')->with('info', 'Has saltado el onboarding. Puedes completarlo más tarde.');
        }

        return redirect()->route('onboarding.index');
    }

    protected function getStepData(int $step, User $user): array
    {
        $carsCount = $user->organization?->cars()->count() ?? 0;
        $org = $user->organization;

        return match ($step) {
            1 => [
                'title' => 'Bienvenido a JJ Import Motors',
                'subtitle' => 'Configura tu organización en 4 simples pasos',
                'icon' => 'building',
            ],
            2 => [
                'title' => 'Añade tu primer vehículo',
                'subtitle' => $carsCount > 0
                    ? "¡Ya tienes {$carsCount} vehículo(s)! Continúa al siguiente paso."
                    : 'Importa un vehículo desde un CSV o crea uno manualmente',
                'icon' => 'car',
                'carsCount' => $carsCount,
                'canAdvance' => $carsCount > 0,
            ],
            3 => [
                'title' => 'Invita a tu equipo',
                'subtitle' => 'Añade colaboradores para trabajar juntos en la importación',
                'icon' => 'users',
                'orgName' => $org?->name ?? 'tu organización',
                'canAdvance' => true,
            ],
            4 => [
                'title' => 'Selecciona tu plan',
                'subtitle' => 'Elige el plan que mejor se adapte a tu negocio',
                'icon' => 'credit-card',
                'currentPlan' => $org?->subscription_plan ?? 'trial',
                'canAdvance' => true,
            ],
            default => [
                'title' => 'Onboarding completado',
                'subtitle' => 'Ya estás listo para usar JJ Import Motors',
                'icon' => 'check-circle',
            ],
        };
    }

    public function skip()
    {
        $user = Auth::user();
        $progress = $user->onboardingProgress;

        if ($progress) {
            $progress->skip();
        }

        return redirect()->route('dashboard')->with('info', 'Has saltado el onboarding. Puedes completarlo más tarde.');
    }
}