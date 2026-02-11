<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    /**
     * Allowed email domain for Google SSO (internal patients).
     */
    private const ALLOWED_DOMAIN = 'uic.edu.ph';

    /**
     * Redirect to Google OAuth consent screen.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')
            ->with(['hd' => self::ALLOWED_DOMAIN]) // hint domain in consent screen
            ->redirect();
    }

    /**
     * Handle Google OAuth callback.
     */
    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            Log::error('Google OAuth callback error: [' . get_class($e) . '] ' . $e->getMessage());
            return redirect()->route('login')
                ->with('error', 'Unable to authenticate with Google. Please try again.');
        }

        $email = strtolower($googleUser->getEmail());

        // Restrict to @uic.edu.ph emails only
        if (!str_ends_with($email, '@' . self::ALLOWED_DOMAIN)) {
            return redirect()->route('login')
                ->with('error', 'Only @uic.edu.ph email addresses are allowed for Google login.');
        }

        // Find existing user by google_id or email
        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $email)
            ->first();

        if ($user) {
            // Update Google info if not set
            $user->update([
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'name' => $user->name ?: $googleUser->getName(),
            ]);

            // If existing user is staff, don't allow Google patient login
            if ($user->isStaff()) {
                return redirect()->route('login')
                    ->with('error', 'This email is associated with a staff account. Please use the standard login.');
            }

            // Ensure the Patient role is assigned
            if (!$user->hasRole('Patient')) {
                $user->assignRole('Patient');
            }
        } else {
            // Create new user with Patient role
            $user = User::create([
                'name' => $googleUser->getName(),
                'email' => $email,
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'password' => null,
                'email_verified_at' => now(),
            ]);

            $user->assignRole('Patient');
        }

        // Link or create patient profile
        $this->linkPatientProfile($user, $googleUser);

        Auth::login($user, remember: true);

        return redirect()->route('patient.dashboard');
    }

    /**
     * Link user account to existing patient record or create a new one.
     */
    private function linkPatientProfile(User $user, $googleUser): void
    {
        // Try to find existing patient by email or user_id
        $patient = Patient::where('user_id', $user->id)
            ->orWhere('email', $user->email)
            ->first();

        if ($patient) {
            // Link existing patient record
            $patient->update([
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
        } else {
            // Parse name from Google
            $nameParts = explode(' ', $googleUser->getName(), 3);
            $firstname = $nameParts[0] ?? '';
            $middlename = count($nameParts) > 2 ? $nameParts[1] : null;
            $lastname = count($nameParts) > 2 ? $nameParts[2] : ($nameParts[1] ?? '');

            // Create new patient profile
            Patient::create([
                'user_id' => $user->id,
                'patient_type' => 'Internal',
                'firstname' => $firstname,
                'middlename' => $middlename,
                'lastname' => $lastname,
                'birthdate' => '2000-01-01', // Placeholder - patient should update
                'gender' => 'N/A',
                'email' => $user->email,
                'status_code' => 1,
                'is_deleted' => 0,
            ]);
        }
    }
}
