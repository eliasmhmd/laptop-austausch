@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="mb-8">
        <h1 class="text-2xl font-semibold text-slate-900">
            Willkommen, {{ $employee->vorname }}!
        </h1>
        <p class="mt-1 text-sm text-slate-500">
            {{ $employee->abteilung }} &middot; KVGG-Nummer {{ $employee->kvgg_nummer }}
        </p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        {{-- Wird in Phase 3 aktiviert. --}}
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-lg font-semibold text-slate-900">Termin buchen</h2>
            <p class="mt-1 text-sm text-slate-500">
                Wählen Sie einen Zeitpunkt für den Austausch Ihres Laptops.
            </p>
            <button type="button" disabled
                class="mt-4 inline-flex cursor-not-allowed rounded-md bg-slate-200 px-4 py-2 text-sm font-medium text-slate-400">
                Demnächst verfügbar
            </button>
        </div>

        {{-- Wird in Phase 4 aktiviert. --}}
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-lg font-semibold text-slate-900">Termin verschieben</h2>
            <p class="mt-1 text-sm text-slate-500">
                Ändern Sie einen bereits gebuchten Termin.
            </p>
            <button type="button" disabled
                class="mt-4 inline-flex cursor-not-allowed rounded-md bg-slate-200 px-4 py-2 text-sm font-medium text-slate-400">
                Demnächst verfügbar
            </button>
        </div>
    </div>
@endsection
