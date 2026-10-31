<?php

namespace App\Modules\Auth\Http\Middleware;

use BackedEnum;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /** Usage : ->middleware('role:admin') ou ->middleware('role:admin,gerant') */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $role = $user?->role instanceof BackedEnum ? $user->role->value : $user?->role;

        if (! $user || ! in_array($role, $roles, true)) {
            abort(403, "Vous n'avez pas le droit d'effectuer cette action.");
        }

        return $next($request);
    }
}