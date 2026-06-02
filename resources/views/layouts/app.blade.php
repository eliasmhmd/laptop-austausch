<!DOCTYPE html>
<html lang="de" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Laptop-Austausch') &middot; Kreis Groß-Gerau</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-100 text-slate-800 antialiased">
    <div class="min-h-full flex flex-col">
        <header class="bg-white shadow-sm">
            <div class="mx-auto max-w-5xl px-4 py-4 flex items-center justify-between">
                <div>
                    <p class="text-lg font-semibold text-slate-900">Laptop-Austausch</p>
                    <p class="text-xs text-slate-500">Kreis Groß-Gerau</p>
                </div>

                @auth('employee')
                    <div class="flex items-center gap-4">
                        <span class="text-sm text-slate-600 hidden sm:inline">
                            Angemeldet als <strong>{{ auth('employee')->user()->full_name }}</strong>
                        </span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="rounded-md bg-slate-200 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-300">
                                Abmelden
                            </button>
                        </form>
                    </div>
                @endauth
            </div>
        </header>

        <main class="flex-1">
            <div class="mx-auto max-w-5xl px-4 py-8">
                @if (session('status'))
                    <div class="mb-6 rounded-md bg-green-50 px-4 py-3 text-sm text-green-800 ring-1 ring-green-200">
                        {{ session('status') }}
                    </div>
                @endif

                @yield('content')
            </div>
        </main>

        <footer class="py-6 text-center text-xs text-slate-400">
            &copy; {{ now()->year }} Kreis Groß-Gerau &middot; IT-Abteilung
        </footer>
    </div>
</body>
</html>
