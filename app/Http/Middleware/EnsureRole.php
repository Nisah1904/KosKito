<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()
                ->route('login')
                ->with('error', 'Silakan masuk terlebih dahulu.');
        }

        if ($roles !== [] && !in_array($user->role, $roles, true)) {
            abort(403, 'Halaman ini hanya dapat diakses oleh role: ' . implode(' / ', $roles) . '. Akun kamu terdaftar sebagai "' . $user->role . '".');
        }

        return $next($request);
    }
}
