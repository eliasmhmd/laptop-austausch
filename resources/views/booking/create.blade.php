@extends('layouts.app')

@section('title', 'Termin bestätigen')

@section('content')
    <div class="mx-auto max-w-lg">
        <div class="rounded-xl bg-white p-8 shadow-sm ring-1 ring-slate-200">
            <h1 class="text-xl font-semibold text-slate-900">Termin bestätigen</h1>
            <p class="mt-1 text-sm text-slate-500">
                Bitte prüfen Sie Ihren gewählten Termin und bestätigen Sie die Buchung.
            </p>

            <dl class="mt-6 divide-y divide-slate-100 rounded-lg bg-slate-50 px-4 ring-1 ring-slate-100">
                <div class="flex justify-between py-3 text-sm">
                    <dt class="text-slate-500">Datum</dt>
                    <dd class="font-medium text-slate-900">{{ $slot->slot_date->translatedFormat('l, d.m.Y') }}</dd>
                </div>
                <div class="flex justify-between py-3 text-sm">
                    <dt class="text-slate-500">Uhrzeit</dt>
                    <dd class="font-medium text-slate-900">
                        {{ \Illuminate\Support\Str::substr($slot->start_time, 0, 5) }}–{{ \Illuminate\Support\Str::substr($slot->end_time, 0, 5) }} Uhr
                    </dd>
                </div>
                <div class="flex justify-between py-3 text-sm">
                    <dt class="text-slate-500">Kalenderwoche</dt>
                    <dd class="font-medium text-slate-900">KW {{ $slot->calendar_week }}</dd>
                </div>
                <div class="flex justify-between py-3 text-sm">
                    <dt class="text-slate-500">Mitarbeiter:in</dt>
                    <dd class="font-medium text-slate-900">{{ $employee->full_name }}</dd>
                </div>
            </dl>

            <p class="mt-4 text-xs text-slate-400">
                Die Angaben zu Ihrem alten und neuen Gerät erfassen Sie im nächsten Schritt.
            </p>

            <form method="POST" action="{{ route('booking.store', $slot) }}" class="mt-6 flex gap-3">
                @csrf
                <a href="{{ route('booking.calendar') }}"
                    class="flex-1 rounded-md bg-slate-100 px-4 py-2.5 text-center text-sm font-medium text-slate-700 hover:bg-slate-200">
                    Anderen Termin wählen
                </a>
                <button type="submit"
                    class="flex-1 rounded-md bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                    Termin verbindlich buchen
                </button>
            </form>
        </div>
    </div>
@endsection
