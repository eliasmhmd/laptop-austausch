@extends('layouts.app')

@section('title', 'Anmelden')

@section('content')
    <div class="mx-auto max-w-md">
        <div class="rounded-xl bg-white p-8 shadow-sm ring-1 ring-slate-200">
            <img src="{{ asset('images/logo-kreis-gg.png') }}" alt="Kreis Groß-Gerau"
                class="mx-auto h-20 w-auto">

            <h1 class="mt-6 text-xl font-semibold text-slate-900">Anmelden</h1>
            <p class="mt-1 text-sm text-slate-600">
                Hier können Sie sich anmelden, um einen Termin für den Austausch Ihres
                Laptops zu vereinbaren.
            </p>
            <p class="mt-2 text-sm text-slate-500">
                Melden Sie sich mit Ihrer KVGG-Nummer und Ihrer PC-Nummer an.
            </p>

            @if ($errors->any())
                <div class="mt-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800 ring-1 ring-red-200">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-5">
                @csrf

                <div>
                    <label for="kvgg_nummer" class="block text-sm font-medium text-slate-700">KVGG-Nummer</label>
                    <input id="kvgg_nummer" name="kvgg_nummer" type="text" required autofocus
                        value="{{ old('kvgg_nummer') }}"
                        placeholder="z. B. KVGG12345"
                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                </div>

                <div>
                    <label for="pc_nummer" class="block text-sm font-medium text-slate-700">PC-Nummer</label>
                    <input id="pc_nummer" name="pc_nummer" type="password" required
                        placeholder="z. B. PC123456"
                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    <p class="mt-1 text-xs text-slate-400">Die PC-Nummer finden Sie auf dem Aufkleber Ihres Geräts.</p>
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" value="1"
                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    Angemeldet bleiben
                </label>

                <button type="submit"
                    class="w-full rounded-md bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Anmelden
                </button>
            </form>
        </div>
    </div>
@endsection
