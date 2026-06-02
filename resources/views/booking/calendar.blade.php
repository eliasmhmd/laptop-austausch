@extends('layouts.app')

@section('title', 'Termin buchen')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Termin buchen</h1>
            <p class="mt-1 text-sm text-slate-500">
                Wählen Sie ein freies Zeitfenster (grün) für den Austausch Ihres Laptops.
            </p>
        </div>
        <a href="{{ route('dashboard') }}" class="text-sm text-slate-500 hover:text-slate-700">&larr; Zurück</a>
    </div>

    @error('slot')
        <div class="mb-6 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800 ring-1 ring-red-200">
            {{ $message }}
        </div>
    @enderror

    @include('booking._calendar', ['filterRoute' => 'booking.calendar', 'selectRoute' => 'booking.create'])
@endsection
