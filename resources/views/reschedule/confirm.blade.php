@extends('layouts.app')

@section('title', 'Verschieben bestätigen')

@section('content')
    <div class="mx-auto max-w-lg">
        <div class="rounded-xl bg-white p-8 shadow-sm ring-1 ring-slate-200">
            <h1 class="text-xl font-semibold text-slate-900">Termin verschieben</h1>
            <p class="mt-1 text-sm text-slate-500">
                Bitte bestätigen Sie die Änderung. Ihr bisheriger Termin wird storniert.
            </p>

            <div class="mt-6 space-y-3">
                <div class="rounded-lg bg-slate-50 p-4 ring-1 ring-slate-100">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Bisher</p>
                    <p class="mt-1 text-sm text-slate-500 line-through">
                        {{ $current->timeSlot->slot_date->translatedFormat('l, d.m.Y') }},
                        {{ \Illuminate\Support\Str::substr($current->timeSlot->start_time, 0, 5) }} Uhr
                    </p>
                </div>
                <div class="flex justify-center text-slate-400">↓</div>
                <div class="rounded-lg bg-green-50 p-4 ring-1 ring-green-200">
                    <p class="text-xs font-medium uppercase tracking-wide text-green-600">Neu</p>
                    <p class="mt-1 text-sm font-semibold text-green-800">
                        {{ $slot->slot_date->translatedFormat('l, d.m.Y') }},
                        {{ \Illuminate\Support\Str::substr($slot->start_time, 0, 5) }}–{{ \Illuminate\Support\Str::substr($slot->end_time, 0, 5) }} Uhr
                        (KW {{ $slot->calendar_week }})
                    </p>
                </div>
            </div>

            <form method="POST" action="{{ route('reschedule.update', $slot) }}" class="mt-6 flex gap-3">
                @csrf
                <a href="{{ route('reschedule.edit') }}"
                    class="flex-1 rounded-md bg-slate-100 px-4 py-2.5 text-center text-sm font-medium text-slate-700 hover:bg-slate-200">
                    Anderen Termin wählen
                </a>
                <button type="submit"
                    class="flex-1 rounded-md bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                    Termin verschieben
                </button>
            </form>
        </div>
    </div>
@endsection
