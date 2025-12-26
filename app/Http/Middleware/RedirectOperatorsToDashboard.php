<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectOperatorsToDashboard
{
    /**
     * Allowed paths for operator role
     * Operators can ONLY access Work Orders page
     */
    protected array $allowedPaths = [
        'pep/work-orders',
        'pep/work-orders/*',
        'pep/change-password',
        'pep/logout',
        'logout',
    ];

    /**
     * Handle an incoming request.
     *
     * @param Closure(Request):Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        // Only apply restrictions to operators
        if ($user && $user->role === 'operator') {
            // Redirect operators from dashboard to work orders page
            if ($request->is('pep') || $request->is('pep/')) {
                return redirect('/pep/work-orders');
            }
            
            // Check if the current path is allowed
            $isAllowed = false;
            foreach ($this->allowedPaths as $path) {
                if ($request->is($path)) {
                    $isAllowed = true;
                    break;
                }
            }
            
            // If accessing a restricted path, redirect to work orders
            if (!$isAllowed && $request->is('pep/*')) {
                return redirect('/pep/work-orders');
            }
        }
        
        return $next($request);
    }
}
