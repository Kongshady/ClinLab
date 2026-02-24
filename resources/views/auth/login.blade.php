<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login &mdash; ClinLab</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Poppins', 'system-ui', 'sans-serif'] },
                    colors: {
                        brand: {
                            50: '#fef2f4', 100: '#fde6ea', 200: '#fbd0d8',
                            400: '#f27d91', 500: '#d2334c', 600: '#c42841', 700: '#a52038',
                        },
                    },
                }
            }
        }
    </script>
    <style>
        * { font-family: 'Poppins', sans-serif; }
        [x-cloak] { display: none !important; }
        body { background: #f0f2f5; }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .fade-up { animation: fadeUp 0.25s ease both; }
        .tab-active { color: #d2334c; border-bottom: 2px solid #d2334c; }
        .tab-inactive { color: #9ca3af; border-bottom: 2px solid transparent; }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center justify-center p-4">

<div class="w-full max-w-sm" x-data="{ tab: 'student', showPass: false }">

    {{-- Brand --}}
    <div class="flex flex-col items-center mb-6">
        {{-- Icon: flask/lab icon in red rounded square --}}
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-brand-500 to-brand-700 flex items-center justify-center shadow-lg shadow-brand-500/30 mb-3">
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 3h6M9 3v6.4a.5.5 0 01-.08.27L5.5 15h13l-3.42-5.33A.5.5 0 0115 9.4V3M9 3H7m10 0h-2"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M5.5 15a6 6 0 0013 0"/>
            </svg>
        </div>
        <h1 class="text-lg font-bold text-gray-900 tracking-tight">ClinLab</h1>
        <p class="text-xs text-gray-400 mt-0.5">Clinical Laboratory Information System</p>
    </div>

    {{-- Card --}}
    <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/60 overflow-hidden">

        {{-- Session messages --}}
        @if (session('status'))
            <div class="mx-5 mt-5 px-4 py-3 rounded-xl bg-green-50 border border-green-100 text-green-700 text-sm">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="mx-5 mt-5 px-4 py-3 rounded-xl bg-red-50 border border-red-100 text-red-700 text-sm">{{ session('error') }}</div>
        @endif

        {{-- Tabs --}}
        <div class="flex border-b border-gray-100">
            <button @click="tab = 'student'"
                :class="tab === 'student' ? 'tab-active' : 'tab-inactive'"
                class="flex-1 flex items-center justify-center gap-2 py-3.5 text-sm font-medium transition-all">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                </svg>
                Student / Faculty
            </button>
            <button @click="tab = 'staff'"
                :class="tab === 'staff' ? 'tab-active' : 'tab-inactive'"
                class="flex-1 flex items-center justify-center gap-2 py-3.5 text-sm font-medium transition-all">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                Lab Staff
            </button>
        </div>

        {{-- LAB STAFF panel --}}
        <div x-show="tab === 'staff'" x-cloak class="fade-up p-5 space-y-4">

            {{-- Portal badge --}}
            <div class="flex items-center gap-3 px-3.5 py-3 rounded-xl bg-brand-50 border border-brand-100">
                <div class="w-8 h-8 rounded-lg bg-brand-100 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-900 leading-tight">Laboratory Management Portal</p>
                    <p class="text-[11px] text-gray-400 mt-0.5">Sign in to manage laboratory operations</p>
                </div>
            </div>

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                {{-- Email --}}
                <div>
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">Email Address</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <input type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
                               placeholder="you@clinlab.test"
                               class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-brand-400 focus:border-transparent transition-colors">
                    </div>
                    @error('email')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password --}}
                <div>
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <input :type="showPass ? 'text' : 'password'" name="password" required autocomplete="current-password"
                               placeholder="Enter your password"
                               class="w-full pl-10 pr-10 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-brand-400 focus:border-transparent transition-colors">
                        <button type="button" @click="showPass = !showPass"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-300 hover:text-gray-500 transition-colors">
                            <svg x-show="!showPass" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg x-show="showPass" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" x-cloak>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Remember + Forgot --}}
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" name="remember"
                               class="w-3.5 h-3.5 rounded border-gray-300 text-brand-500 focus:ring-brand-400 focus:ring-offset-0">
                        <span class="text-xs text-gray-500">Remember me</span>
                    </label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700 transition-colors">
                            Forgot password?
                        </a>
                    @endif
                </div>

                {{-- Submit --}}
                <button type="submit"
                        class="w-full flex items-center justify-center gap-2 py-2.5 bg-brand-500 hover:bg-brand-600 active:scale-[0.98] text-white text-sm font-semibold rounded-xl shadow-md shadow-brand-500/20 transition-all">
                    Sign In
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </button>
            </form>
        </div>

        {{-- STUDENT / FACULTY panel --}}
        <div x-show="tab === 'student'" x-cloak class="fade-up p-5 space-y-4">

            {{-- Portal badge --}}
            <div class="flex items-center gap-3 px-3.5 py-3 rounded-xl bg-blue-50 border border-blue-100">
                <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-900 leading-tight">Student / Faculty Portal</p>
                    <p class="text-[11px] text-gray-400 mt-0.5">View your lab results using your UIC Google account</p>
                </div>
            </div>

            <a href="{{ route('auth.google.redirect') }}"
               class="group w-full flex items-center justify-center gap-3 py-2.5 px-4 bg-white border border-gray-200 rounded-xl text-sm font-semibold text-gray-700 hover:border-blue-300 hover:bg-blue-50/60 hover:shadow-sm active:scale-[0.98] transition-all">
                <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                Sign in with Google
            </a>

            <p class="text-center text-xs text-gray-400 flex items-center justify-center gap-1">
                <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                Only <span class="font-semibold text-gray-500 mx-0.5">@uic.edu.ph</span> accounts are accepted
            </p>
        </div>

    </div>

    {{-- Footer --}}
    <p class="text-center text-[11px] text-gray-400 mt-5">&copy; {{ date('Y') }} ClinLab &mdash; University of the Immaculate Conception</p>

</div>
</body>
</html>
