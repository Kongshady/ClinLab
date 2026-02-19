<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Access Denied</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        blue: {
                            50: '#fef2f4', 100: '#fde6ea', 200: '#fbd0d8', 300: '#f7a9b6', 400: '#f27d91',
                            500: '#d2334c', 600: '#c42841', 700: '#a52038', 800: '#891d33', 900: '#7b1d31', 950: '#450a19',
                        },
                        cyan: {
                            50: '#fff5f6', 100: '#ffe0e4', 200: '#ffc7cf', 300: '#ffa3b1', 400: '#e8607a',
                            500: '#d94863', 600: '#c4354f', 700: '#a52a41', 800: '#8c2539', 900: '#782234', 950: '#430d19',
                        },
                    },
                }
            }
        }
    </script>
</head>
<body class="bg-slate-900 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <div class="text-center">
            <!-- Lock Icon -->
            <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-red-500/20 mb-6">
                <svg class="w-12 h-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>

            <!-- Error Code -->
            <h1 class="text-7xl font-bold text-red-400 mb-4">
                403
            </h1>

            <!-- Error Title -->
            <h2 class="text-2xl font-semibold text-white mb-3">
                Access Denied
            </h2>

            <!-- Error Message -->
            <p class="text-slate-400 mb-8 leading-relaxed">
                You don't have permission to access this resource. Please contact your administrator if you believe this is an error.
            </p>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="javascript:history.back()" class="inline-flex items-center justify-center px-6 py-3 rounded-xl font-medium text-slate-300 bg-slate-800 hover:bg-slate-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Go Back
                </a>
                <a href="/" class="inline-flex items-center justify-center px-6 py-3 rounded-xl font-medium text-white bg-blue-500 hover:bg-blue-600 shadow-lg shadow-blue-500/30 transition-all">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Dashboard
                </a>
            </div>
        </div>

        <!-- Additional Info -->
        <div class="mt-12 pt-8 border-t border-slate-700/50 text-center">
            <p class="text-sm text-slate-500">
                Error Code: <span class="font-mono text-slate-400">ERR_FORBIDDEN</span>
            </p>
            @if($exception->getMessage())
            <p class="text-sm text-slate-500 mt-2">
                {{ $exception->getMessage() }}
            </p>
            @endif
        </div>
    </div>
</body>
</html>
