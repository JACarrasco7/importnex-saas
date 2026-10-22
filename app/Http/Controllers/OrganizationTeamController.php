<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Equipo y acceso de la organización: miembros y token de API.
 * Solo el propietario (role=owner) de la propia organización puede usarlo.
 */
class OrganizationTeamController extends Controller
{
    /** Añade un miembro y le envía el email para elegir su contraseña. */
    public function storeMember(Request $request, Organization $organization): RedirectResponse
    {
        $this->authorizeOwner($request, $organization);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'role' => ['required', Rule::in(['owner', 'operator'])],
        ]);

        if ($organization->limitReached('users')) {
            return back()->with('error', 'Has alcanzado el máximo de usuarios de tu plan.');
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'organization_id' => $organization->id,
            'password' => Hash::make(Str::password(32)),
            'email_verified_at' => now(),
        ]);

        try {
            Password::broker()->sendResetLink(['email' => $user->email]);
        } catch (\Throwable $e) {
            Log::warning('team.invite_mail_failed', ['user_id' => $user->id, 'message' => $e->getMessage()]);

            return back()->with('error', 'Usuario creado, pero no se pudo enviar el email. Pídele que use «¿Olvidaste tu contraseña?».');
        }

        return back()->with('success', "Hemos enviado a {$user->email} un enlace para crear su contraseña.");
    }

    public function destroyMember(Request $request, Organization $organization, User $user): RedirectResponse
    {
        $this->authorizeOwner($request, $organization);
        abort_unless($user->organization_id === $organization->id, 404);

        if ($user->is($request->user())) {
            return back()->with('error', 'No puedes eliminarte a ti mismo.');
        }

        $user->delete();

        return back()->with('success', "{$user->name} ya no tiene acceso.");
    }

    /** Genera un token de API nuevo (invalida el anterior) y lo muestra una sola vez. */
    public function issueApiToken(Request $request, Organization $organization): RedirectResponse
    {
        $this->authorizeOwner($request, $organization);

        return back()
            ->with('success', 'Token de API generado. Cópialo ahora: no se volverá a mostrar.')
            ->with('api_token', $organization->issueApiToken());
    }

    public function revokeApiToken(Request $request, Organization $organization): RedirectResponse
    {
        $this->authorizeOwner($request, $organization);

        $organization->forceFill(['api_token_hash' => null])->save();

        return back()->with('success', 'Token de API revocado.');
    }

    private function authorizeOwner(Request $request, Organization $organization): void
    {
        $user = $request->user();
        abort_unless($user && $user->organization_id === $organization->id && $user->isOwner(), 403);
    }
}
