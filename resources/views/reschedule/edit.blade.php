@extends('layouts.app')

@section('title', 'Termin verschieben')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Termin verschieben</h1>
            <p class="mt-1 text-sm text-slate-500">
                Wählen Sie ein neues freies Zeitfenster. Ihr bisheriger Termin wird dabei storniert.
            </p>
        </div>
        <a href="{{ route('dashboard') }}" class="text-sm text-slate-500 hover:text-slate-700">&larr; Zurück</a>
    </div>

    {{-- Aktueller Termin --}}
    <div class="mb-6 rounded-xl bg-amber-50 p-4 text-sm ring-1 ring-amber-200">
        <span class="font-medium text-amber-900">Aktueller Termin:</span>
        <span class="text-amber-800">
            {{ $current->timeSlot->slot_date->translatedFormat('l, d.m.Y') }}
            um {{ \Illuminate\Support\Str::substr($current->timeSlot->start_time, 0, 5) }} Uhr
            (KW {{ $current->timeSlot->calendar_week }})
        </span>
    </div>

    @error('slot')
        <div class="mb-6 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800 ring-1 ring-red-200">
            {{ $message }}
        </div>
    @enderror

    @include('booking._calendar', ['filterRoute' => 'reschedule.edit', 'selectRoute' => 'reschedule.confirm'])
@endsection
