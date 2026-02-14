<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ClinLab</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Poppins', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        blue: {
                            50: '#fef2f4',
                            100: '#fde6ea',
                            200: '#fbd0d8',
                            300: '#f7a9b6',
                            400: '#f27d91',
                            500: '#d1324a',
                            600: '#c42841',
                            700: '#a52038',
                            800: '#891d33',
                            900: '#7b1d31',
                            950: '#450a19',
                        },
                        cyan: {
                            50: '#fff5f6',
                            100: '#ffe0e4',
                            200: '#ffc7cf',
                            300: '#ffa3b1',
                            400: '#e8607a',
                            500: '#d94863',
                            600: '#c4354f',
                            700: '#a52a41',
                            800: '#8c2539',
                            900: '#782234',
                            950: '#430d19',
                        },
                    },
                }
            }
        }
    </script>
    <style>
        * { font-family: 'Poppins', sans-serif; }
        .tab-active { border-bottom: 3px solid; }
        .card-slide { transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s ease; }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-100 via-blue-50 to-cyan-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">

        <!-- Logo/Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-600 to-cyan-400 mb-4 shadow-lg shadow-blue-500/25">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-1">ClinLab</h1>
            <p class="text-gray-500 text-sm">Clinical Laboratory Information System</p>
        </div>

        <!-- Session/Error Messages -->
        @if (session('status'))
            <div class="mb-4 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">
                {{ session('status') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mb-4 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
                {{ session('error') }}
            </div>
        @endif

        <!-- Login Card -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-xl overflow-hidden">

            <!-- Tab Switcher -->
            <div class="flex border-b border-gray-200">
                <button id="tab-student" onclick="switchTab('student')"
                        class="flex-1 py-4 text-sm font-semibold text-center transition-all tab-active border-blue-500 text-blue-600 bg-blue-50/50">
                    <div class="flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                        </svg>
                        Student / Patient
                    </div>
                </button>
                <button id="tab-employee" onclick="switchTab('employee')"
                        class="flex-1 py-4 text-sm font-semibold text-center transition-all border-b-3 border-transparent text-gray-400 hover:text-gray-600">
                    <div class="flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        Employee
                    </div>
                </button>
            </div>

            <div class="relative overflow-hidden">

                <!-- Student / Patient Panel -->
                <div id="panel-student" class="card-slide p-8">
                    <div class="text-center mb-6">
                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-blue-100 mb-3">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900">Student Portal</h2>
                        <p class="text-gray-500 text-sm mt-1">Sign in with your UIC Google account to view your lab results</p>
                    </div>

                    <a href="{{ route('auth.google.redirect') }}"
                       class="group w-full flex items-center justify-center gap-3 px-6 py-3.5 bg-white border-2 border-gray-200 rounded-xl text-gray-700 font-semibold hover:border-blue-300 hover:bg-blue-50/50 hover:shadow-md transition-all">
                        <svg class="w-5 h-5" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        <span>Sign in with Google</span>
                        <svg class="w-4 h-4 text-gray-400 group-hover:text-blue-500 group-hover:translate-x-0.5 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>

                    <div class="mt-6 flex items-center gap-2 justify-center text-xs text-gray-400">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        Only <span class="font-medium text-gray-500">@uic.edu.ph</span> accounts are accepted
                    </div>
                </div>

                <!-- Employee Panel -->
                <div id="panel-employee" class="card-slide p-8 hidden">
                    <div class="text-center mb-6">
                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-blue-100 mb-3">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900">Employee Login</h2>
                        <p class="text-gray-500 text-sm mt-1">Laboratory staff sign in with your credentials</p>
                    </div>

                    <form method="POST" action="{{ route('login') }}" class="space-y-5">
                        @csrf

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
                                       placeholder="you@clinlab.test"
                                       class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all">
                            </div>
                            @error('email')
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                </div>
                                <input id="password" type="password" name="password" required autocomplete="current-password"
                                       placeholder="Enter your password"
                                       class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all">
                            </div>
                            @error('password')
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Remember + Forgot -->
                        <div class="flex items-center justify-between">
                            <label for="remember_me" class="flex items-center cursor-pointer">
                                <input id="remember_me" type="checkbox" name="remember"
                                       class="w-4 h-4 rounded border-gray-300 text-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-0">
                                <span class="ml-2 text-sm text-gray-600">Remember me</span>
                            </label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="text-sm text-blue-600 hover:text-blue-700 transition-colors">
                                    Forgot password?
                                </a>
                            @endif
                        </div>

                        <!-- Submit -->
                        <button type="submit"
                                class="w-full px-6 py-3 bg-gradient-to-r from-blue-500 to-cyan-400 text-white font-semibold rounded-xl shadow-lg shadow-blue-500/25 hover:shadow-blue-500/40 hover:scale-[1.02] active:scale-[0.98] transition-all">
                            Sign In
                        </button>
                    </form>
                </div>

            </div>
        </div>

        <!-- Footer -->
        <p class="text-center text-xs text-gray-400 mt-6">&copy; {{ date('Y') }} ClinLab &mdash; University of the Immaculate Conception</p>
    </div>

    <script>
        function switchTab(tab) {
            const studentTab = document.getElementById('tab-student');
            const employeeTab = document.getElementById('tab-employee');
            const studentPanel = document.getElementById('panel-student');
            const employeePanel = document.getElementById('panel-employee');

            if (tab === 'student') {
                studentTab.className = 'flex-1 py-4 text-sm font-semibold text-center transition-all tab-active border-blue-500 text-blue-600 bg-blue-50/50';
                employeeTab.className = 'flex-1 py-4 text-sm font-semibold text-center transition-all border-b-3 border-transparent text-gray-400 hover:text-gray-600';
                studentPanel.classList.remove('hidden');
                employeePanel.classList.add('hidden');
            } else {
                employeeTab.className = 'flex-1 py-4 text-sm font-semibold text-center transition-all tab-active border-blue-500 text-blue-600 bg-blue-50/50';
                studentTab.className = 'flex-1 py-4 text-sm font-semibold text-center transition-all border-b-3 border-transparent text-gray-400 hover:text-gray-600';
                employeePanel.classList.remove('hidden');
                studentPanel.classList.add('hidden');
            }
        }

        // Auto-switch to employee tab if there are validation errors (from form submit)
        @if($errors->any())
            switchTab('employee');
        @endif
    </script>
</body>
</html>
