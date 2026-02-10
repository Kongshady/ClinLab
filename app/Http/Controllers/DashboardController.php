<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Redirect to the appropriate dashboard based on user role
     */
    public function index()
    {
        $user = auth()->user();

        // Patient role -> patient dashboard
        if ($user->hasRole('Patient')) {
            return redirect()->route('patient.dashboard');
        }

        // Redirect based on role priority (in case user has multiple roles)
        if ($user->hasRole('Laboratory Manager')) {
            return redirect()->route('dashboard.manager');
        }
        
        if ($user->hasRole('MIT Staff')) {
            return redirect()->route('dashboard.mit');
        }
        
        if ($user->hasRole('Staff-in-Charge')) {
            return redirect()->route('dashboard.staff');
        }
        
        if ($user->hasRole('Secretary')) {
            return redirect()->route('dashboard.secretary');
        }

        // Default fallback - show a generic dashboard or error
        return redirect()->route('dashboard.staff');
    }

    public function manager()
    {
        return view('dashboards.manager');
    }

    public function staff()
    {
        return view('dashboards.staff');
    }

    public function mit()
    {
        return view('dashboards.mit');
    }

    public function secretary()
    {
        return view('dashboards.secretary');
    }
}
