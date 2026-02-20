<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\RedirectByRole;
use App\Models\Patient;
use App\Models\UicDirectoryPerson;
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

        // Use centralized role-based redirect
        $destination = RedirectByRole::destinationFor($user) ?? route('login');
        return redirect($destination);
    }

    /**
     * Link user account to patient record using UIC directory data.
     * Falls back to Google profile if not found in directory.
     */
    private function linkPatientProfile(User $user, $googleUser): void
    {
        // Try to find existing patient by user_id or email
        $patient = Patient::where('user_id', $user->id)
            ->orWhere('email', $user->email)
            ->first();

        // Look up in local UIC directory (synced from API)
        $directoryPerson = UicDirectoryPerson::where('email', strtolower($user->email))->first();

        if ($patient) {
            // Patient record exists — enrich with directory data if available
            $updates = [
                'user_id' => $user->id,
                'email' => $user->email,
            ];

            if ($directoryPerson) {
                $updates['firstname'] = $directoryPerson->first_name;
                $updates['middle_name'] = $directoryPerson->middle_name;
                $updates['lastname'] = $directoryPerson->last_name;
                $updates['external_ref_id'] = $directoryPerson->external_ref_id;
                $updates['patient_type'] = 'Internal';
            }

            $patient->update($updates);
        } else {
            // Create new patient record
            if ($directoryPerson) {
                // Use official UIC directory data
                Patient::create([
                    'user_id'         => $user->id,
                    'patient_type'    => 'Internal',
                    'firstname'       => $directoryPerson->first_name,
                    'middlename'      => $directoryPerson->middle_name,
                    'lastname'        => $directoryPerson->last_name,
                    'birthdate'       => '2000-01-01', // Placeholder — not in directory
                    'gender'          => 'N/A',
                    'email'           => $user->email,
                    'external_ref_id' => $directoryPerson->external_ref_id,
                    'status_code'     => 1,
                    'is_deleted'      => 0,
                ]);
            } else {
                // Fallback: parse name from Google profile
                $nameParts = explode(' ', $googleUser->getName(), 3);
                $firstname = $nameParts[0] ?? '';
                $middlename = count($nameParts) > 2 ? $nameParts[1] : null;
                $lastname = count($nameParts) > 2 ? $nameParts[2] : ($nameParts[1] ?? '');

                Patient::create([
                    'user_id'      => $user->id,
                    'patient_type' => 'Internal',
                    'firstname'    => $firstname,
                    'middlename'   => $middlename,
                    'lastname'     => $lastname,
                    'birthdate'    => '2000-01-01',
                    'gender'       => 'N/A',
                    'email'        => $user->email,
                    'status_code'  => 1,
                    'is_deleted'   => 0,
                ]);

                Log::info("[Google Login] No UIC directory match for {$user->email} — created patient from Google profile.");
            }
        }
    }
}
