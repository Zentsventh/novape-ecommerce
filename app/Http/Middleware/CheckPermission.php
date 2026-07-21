<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, $permiso)
    {
        if (!auth()->check()) {
            return redirect('/login');
        }

        \Log::info('CheckPermission check', [
            'user_id' => auth()->id(),
            'user_email' => auth()->user()->email,
            'permiso' => $permiso,
            'tiene_permiso' => auth()->user()->tienePermiso($permiso)
        ]);

        if (!auth()->user()->tienePermiso($permiso)) {
            abort(403, 'NO TIENES PERMISO PARA ACCEDER A ESTA SECCIÓN.');
        }

        return $next($request);
    }
}
