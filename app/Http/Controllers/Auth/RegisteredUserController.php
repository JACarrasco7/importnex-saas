<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'company' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // SaaS: cada alta crea su propia organización (prueba gratuita) y el
        // usuario queda como propietario de ella.
        $user = DB::transaction(function () use ($request) {
            $organization = Organization::create([
                'name' => $request->company,
                'plan' => 'starter',
                'trial_ends_at' => now()->addDays((int) config('subscription.trial_days', 14)),
            ]);

            return User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'owner',
                'organization_id' => $organization->id,
                // La columna tiene 'en' por defecto; el producto es en español.
                'locale' => config('app.locale', 'es'),
            ]);
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('onboarding.index');
    }
}
