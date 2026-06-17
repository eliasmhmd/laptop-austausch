@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="mb-8">
        <h1 class="text-2xl font-semibold text-slate-900">
            Willkommen, {{ trim($employee->vorname.' '.$employee->nachname) }}!
        </h1>
        <p class="mt-1 text-sm text-slate-500">
            {{ $employee->abteilung }} &middot; KVGG-Nummer {{ $employee->kvgg_nummer }}
        </p>
    </div>

    @if ($activeBooking)
        {{-- Mitarbeiter:in hat bereits einen Termin. --}}
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900">Ihr gebuchter Termin</h2>
                <span class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">bestätigt</span>
            </div>
            <p class="mt-2 text-slate-700">
                {{ $activeBooking->timeSlot->slot_date->translatedFormat('l, d.m.Y') }}
                um {{ \Illuminate\Support\Str::substr($activeBooking->timeSlot->start_time, 0, 5) }} Uhr
                <span class="text-slate-400">(KW {{ $activeBooking->timeSlot->calendar_week }})</span>
                @if ($room)
                    in {{ $room }}
                @endif
            </p>

            <div class="mt-4 flex flex-col gap-2 sm:flex-row">
                <a href="{{ route('booking.show', $activeBooking) }}"
                    class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                    Termin ansehen
                </a>
                <a href="{{ route('reschedule.edit') }}"
                    class="rounded-md bg-white px-4 py-2 text-sm font-medium text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50">
                    Termin verschieben
                </a>
            </div>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-lg font-semibold text-slate-900">Termin buchen</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Wählen Sie einen Zeitpunkt für den Austausch Ihres Laptops.
                </p>
                <a href="{{ route('booking.calendar') }}"
                    class="mt-4 inline-flex rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                    Termin buchen
                </a>
            </div>

            {{-- Wird in Phase 4 aktiviert (setzt eine bestehende Buchung voraus). --}}
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-lg font-semibold text-slate-900">Termin verschieben</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Sie haben noch keinen Termin gebucht.
                </p>
                <button type="button" disabled
                    class="mt-4 inline-flex cursor-not-allowed rounded-md bg-slate-200 px-4 py-2 text-sm font-medium text-slate-400">
                    Termin verschieben
                </button>
            </div>
        </div>
    @endif
@endsection
