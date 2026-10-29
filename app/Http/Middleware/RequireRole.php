<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        abort_unless($request->user()?->activo && in_array($request->user()->rol, $roles, true), 403, 'No tienes permiso para realizar esta acción.');

        return $next($request);
    }
}
