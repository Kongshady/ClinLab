<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectByRole
{
    /**
     * Centralized role → dashboard mapping.
     * Order matters: first match wins for users with multiple roles.
     */
    public const ROLE_REDIRECTS = [
        'Patient'            => '/patient/dashboard',
        'Laboratory Manager' => '/dashboard/manager',
        'MIT Staff'          => '/dashboard/mit',
        'Staff-in-Charge'    => '/dashboard/staff',
        'Secretary'          => '/dashboard/secretary',
    ];

    /**
     * Handle an incoming request.
     * Used on the /redirect-after-login route to send each user to their dashboard.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($user = $request->user()) {
            if ($url = self::destinationFor($user)) {
                return redirect($url);
            }

            // No recognised role – log them out with an error
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Your account has no valid role. Please contact the laboratory administrator.');
        }

        return $next($request);
    }

    /**
     * Return the dashboard URL for a given user, or null if no role matches.
     */
    public static function destinationFor($user): ?string
    {
        foreach (self::ROLE_REDIRECTS as $role => $url) {
            if ($user->hasRole($role)) {
                return $url;
            }
        }

        return null;
    }
}
