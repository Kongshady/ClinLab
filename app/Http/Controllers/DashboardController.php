<?php

namespace App\Http\Controllers;

use App\Http\Middleware\RedirectByRole;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Redirect to the appropriate dashboard based on user role
     */
    public function index()
    {
        $user = auth()->user();
        $destination = RedirectByRole::destinationFor($user);

        if ($destination) {
            return redirect($destination);
        }

        // No recognised role — fallback
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
