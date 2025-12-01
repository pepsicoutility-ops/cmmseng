<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectOperatorsToDashboard
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        // Redirect operators from dashboard to work orders page
        if ($user && $user->role === 'operator' && ($request->is('pep') || $request->is('pep/'))) {
            return redirect('/pep/work-orders');
        }
        
        return $next($request);
    }
}
