<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Certificate - ClinLab</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-slate-100 flex flex-col">

    {{-- Header --}}
    <header class="bg-white/80 backdrop-blur-sm border-b border-gray-200 sticky top-0 z-10">
        <div class="max-w-4xl mx-auto px-6 py-4 flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-blue-600 to-cyan-500 flex items-center justify-center shadow-sm">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-lg font-bold text-gray-900">ClinLab</h1>
                <p class="text-xs text-gray-500">Certificate Verification</p>
            </div>
        </div>
    </header>

    {{-- Main --}}
    <main class="flex-1 flex items-start justify-center px-4 py-12">
        <div class="w-full max-w-lg">
            {{-- Search Box --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden mb-6">
                <div class="p-8 text-center">
                    <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 mb-2">Verify a Certificate</h2>
                    <p class="text-sm text-gray-500 mb-6">Enter the certificate number or verification code to check its authenticity.</p>

                    <form method="GET" action="{{ route('certificates.public.verify') }}" class="flex gap-3">
                        <input type="text" name="code" value="{{ $code ?? '' }}"
                               placeholder="e.g. CERT-2026-00001"
                               class="flex-1 px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all"
                               required>
                        <button type="submit"
                                class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 text-white font-medium text-sm rounded-xl shadow-sm transition-all">
                            Verify
                        </button>
                    </form>
                </div>
            </div>

            {{-- Results --}}
            @if(isset($result))
                @if($result === null)
                    {{-- No search yet, do nothing --}}
                @elseif($result['found'] ?? false)
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                        {{-- Status Banner --}}
                        @if($result['valid'])
                        <div class="flex items-center gap-3 p-5 bg-emerald-50 border-b border-emerald-200">
                            <div class="w-12 h-12 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <div>
                                <p class="text-lg font-bold text-emerald-800">Certificate is Valid</p>
                                <p class="text-sm text-emerald-600">This certificate has been verified and is currently active.</p>
                            </div>
                        </div>
                        @else
                        <div class="flex items-center gap-3 p-5 bg-red-50 border-b border-red-200">
                            <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </div>
                            <div>
                                <p class="text-lg font-bold text-red-800">Certificate is Invalid</p>
                                <p class="text-sm text-red-600">This certificate is {{ strtolower($result['status']) }} or has expired.</p>
                            </div>
                        </div>
                        @endif

                        {{-- Details --}}
                        <div class="divide-y divide-gray-100">
                            <div class="flex justify-between items-center px-6 py-4">
                                <span class="text-sm text-gray-500">Certificate No.</span>
                                <span class="text-sm font-bold text-gray-900 font-mono">{{ $result['number'] }}</span>
                            </div>
                            <div class="flex justify-between items-center px-6 py-4">
                                <span class="text-sm text-gray-500">Type</span>
                                <span class="text-sm font-medium text-gray-900">{{ $result['type'] }}</span>
                            </div>
                            <div class="flex justify-between items-center px-6 py-4">
                                <span class="text-sm text-gray-500">Status</span>
                                @php
                                    $sBg = match(strtolower($result['status'])) {
                                        'issued' => 'bg-emerald-100 text-emerald-700',
                                        'revoked' => 'bg-red-100 text-red-700',
                                        'expired' => 'bg-gray-100 text-gray-600',
                                        default => 'bg-amber-100 text-amber-700',
                                    };
                                @endphp
                                <span class="px-3 py-1 text-xs font-semibold rounded-full {{ $sBg }}">{{ $result['status'] }}</span>
                            </div>
                            @if($result['issue_date'])
                            <div class="flex justify-between items-center px-6 py-4">
                                <span class="text-sm text-gray-500">Issue Date</span>
                                <span class="text-sm font-medium text-gray-900">{{ $result['issue_date'] }}</span>
                            </div>
                            @endif
                            @if($result['valid_until'])
                            <div class="flex justify-between items-center px-6 py-4">
                                <span class="text-sm text-gray-500">Valid Until</span>
                                <span class="text-sm font-medium text-gray-900">{{ $result['valid_until'] }}</span>
                            </div>
                            @endif
                            @if($result['patient'])
                            <div class="flex justify-between items-center px-6 py-4">
                                <span class="text-sm text-gray-500">Issued To</span>
                                <span class="text-sm font-medium text-gray-900">{{ $result['patient'] }}</span>
                            </div>
                            @endif
                            @if($result['issued_by'])
                            <div class="flex justify-between items-center px-6 py-4">
                                <span class="text-sm text-gray-500">Issued By</span>
                                <span class="text-sm font-medium text-gray-900">{{ $result['issued_by'] }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="flex items-center gap-3 p-5 bg-amber-50">
                            <div class="w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                            </div>
                            <div>
                                <p class="text-lg font-bold text-amber-800">Certificate Not Found</p>
                                <p class="text-sm text-amber-600">No certificate was found matching "{{ $code }}". Please check the number and try again.</p>
                            </div>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </main>

    {{-- Footer --}}
    <footer class="py-6 text-center text-xs text-gray-400">
        <p>&copy; {{ date('Y') }} ClinLab - Clinical Laboratory Information System</p>
    </footer>
</body>
</html>
