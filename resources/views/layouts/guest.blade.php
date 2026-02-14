<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Tailwind CSS -->
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        colors: {
                            blue: {
                                50: '#fef2f4', 100: '#fde6ea', 200: '#fbd0d8', 300: '#f7a9b6', 400: '#f27d91',
                                500: '#d1324a', 600: '#c42841', 700: '#a52038', 800: '#891d33', 900: '#7b1d31', 950: '#450a19',
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
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
            <div>
                <a href="/">
                    <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
