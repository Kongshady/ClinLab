<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ClinLab</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gradient-to-br from-blue-50 to-cyan-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo/Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-500 to-cyan-400 mb-4 shadow-lg">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Welcome Back</h1>
            <p class="text-gray-600">Sign in to your ClinLab account</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white rounded-2xl border border-gray-200 p-8 shadow-xl">
            <!-- Session Status -->
            @if (session('status'))
                <div class="mb-4 px-4 py-3 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-6">
                @csrf

                <!-- Email Address -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input id="email" 
                           type="email" 
                           name="email" 
                           value="{{ old('email') }}" 
                           required 
                           autofocus 
                           autocomplete="username"
                           class="w-full px-4 py-3 bg-white border border-gray-300 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                    @error('email')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                    <input id="password" 
                           type="password" 
                           name="password" 
                           required 
                           autocomplete="current-password"
                           class="w-full px-4 py-3 bg-white border border-gray-300 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                    @error('password')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Remember Me -->
                <div class="flex items-center justify-between">
                    <label for="remember_me" class="flex items-center cursor-pointer">
                        <input id="remember_me" 
                               type="checkbox" 
                               name="remember"
                               class="w-4 h-4 rounded border-gray-300 bg-white text-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-0">
                        <span class="ml-2 text-sm text-gray-600">Remember me</span>
                    </label>

                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-sm text-blue-600 hover:text-blue-700 transition-colors">
                            Forgot password?
                        </a>
                    @endif
                </div>

                <!-- Submit Button -->
                <button type="submit" 
                        class="w-full px-6 py-3 bg-gradient-to-r from-blue-500 to-cyan-400 text-white font-semibold rounded-xl shadow-lg shadow-blue-500/30 hover:shadow-blue-500/50 hover:scale-[1.02] active:scale-[0.98] transition-all">
                    Sign In
                </button>
            </form>

            <!-- Demo Accounts Info -->
            <div class="mt-6 pt-6 border-t border-gray-200">
                <p class="text-xs text-gray-500 text-center mb-3">Demo Accounts (password: <span class="text-gray-700 font-medium">password</span>)</p>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="px-3 py-2 bg-blue-50 rounded-lg border border-blue-100">
                        <p class="text-gray-600">Manager</p>
                        <p class="text-blue-600 truncate font-medium">manager@clinlab.test</p>
                    </div>
                    <div class="px-3 py-2 bg-cyan-50 rounded-lg border border-cyan-100">
                        <p class="text-gray-600">Staff</p>
                        <p class="text-cyan-600 truncate font-medium">staff@clinlab.test</p>
                    </div>
                    <div class="px-3 py-2 bg-green-50 rounded-lg border border-green-100">
                        <p class="text-gray-600">MIT</p>
                        <p class="text-green-600 truncate font-medium">mit@clinlab.test</p>
                    </div>
                    <div class="px-3 py-2 bg-purple-50 rounded-lg border border-purple-100">
                        <p class="text-gray-600">Secretary</p>
                        <p class="text-purple-600 truncate font-medium">secretary@clinlab.test</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
