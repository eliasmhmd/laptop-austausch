@extends('layouts.app')

@section('title', 'Ihr Termin')

@section('content')
    <div class="mx-auto max-w-lg">
        <div class="rounded-xl bg-white p-8 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-green-100 text-green-600">✓</span>
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">Termin gebucht</h1>
                    <p class="text-sm text-slate-500">Ihr Termin für den Laptop-Austausch steht fest.</p>
                </div>
            </div>

            <dl class="mt-6 divide-y divide-slate-100 rounded-lg bg-slate-50 px-4 ring-1 ring-slate-100">
                <div class="flex justify-between py-3 text-sm">
                    <dt class="text-slate-500">Datum</dt>
                    <dd class="font-medium text-slate-900">{{ $booking->timeSlot->slot_date->translatedFormat('l, d.m.Y') }}</dd>
                </div>
                <div class="flex justify-between py-3 text-sm">
                    <dt class="text-slate-500">Uhrzeit</dt>
                    <dd class="font-medium text-slate-900">
                        {{ \Illuminate\Support\Str::substr($booking->timeSlot->start_time, 0, 5) }}–{{ \Illuminate\Support\Str::substr($booking->timeSlot->end_time, 0, 5) }} Uhr
                    </dd>
                </div>
                <div class="flex justify-between py-3 text-sm">
                    <dt class="text-slate-500">Kalenderwoche</dt>
                    <dd class="font-medium text-slate-900">KW {{ $booking->timeSlot->calendar_week }}</dd>
                </div>
                <div class="flex justify-between py-3 text-sm">
                    <dt class="text-slate-500">Status</dt>
                    <dd><span class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">bestätigt</span></dd>
                </div>
            </dl>

            <div class="mt-6 flex flex-col gap-2 sm:flex-row">
                <a href="{{ route('dashboard') }}"
                    class="flex-1 rounded-md bg-slate-100 px-4 py-2.5 text-center text-sm font-medium text-slate-700 hover:bg-slate-200">
                    Zum Dashboard
                </a>
                @if ($booking->isActive())
                    <a href="{{ route('reschedule.edit') }}"
                        class="flex-1 rounded-md bg-white px-4 py-2.5 text-center text-sm font-medium text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50">
                        Termin verschieben
                    </a>
                @endif
                {{-- Der iCal-Download ("Zum Kalender hinzufügen") folgt in Phase 6. --}}
            </div>
        </div>
    </div>
@endsection
