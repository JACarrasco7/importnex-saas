<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LocaleController extends Controller
{
    public function update(Request $request)
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', 'in:en,es'],
        ]);

        $user = $request->user();
        $locale = $validated['locale'];

        if ($user) {
            $user->update(['locale' => $locale]);
        }

        $response = response()->json([
            'message' => 'Locale updated successfully',
            'locale' => $locale,
        ]);

        // Set cookie for all users
        $response->withCookie(cookie()->make('locale', $locale, 60 * 24 * 365)); // 1 year

        return $response;
    }
}
