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
                @if ($room)
                    <div class="flex justify-between py-3 text-sm">
                        <dt class="text-slate-500">Ort</dt>
                        <dd class="font-medium text-slate-900">{{ $room }}</dd>
                    </div>
                @endif
                <div class="flex justify-between py-3 text-sm">
                    <dt class="text-slate-500">Status</dt>
                    <dd><span class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">bestätigt</span></dd>
                </div>
            </dl>

            @if ($booking->isActive())
                <div class="mt-6 rounded-lg border border-slate-200 p-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-slate-900">Software für das neue Gerät</h2>
                        <a href="{{ route('config.edit', $booking) }}" class="text-sm font-medium text-blue-600 hover:underline">
                            Ändern
                        </a>
                    </div>

                    @if ($booking->software->isNotEmpty())
                        <ul class="mt-3 flex flex-wrap gap-2">
                            @foreach ($booking->software as $sw)
                                <li class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-700">
                                    {{ $sw->is_custom ? $sw->custom_software_name : $sw->softwareCatalog?->name }}
                                    @if ($sw->is_custom)<span class="text-slate-400">· Spezial</span>@endif
                                </li>
                            @endforeach
                        </ul>
                        @if ($booking->laptopConfig?->old_manufacturer)
                            <p class="mt-3 text-xs text-slate-500">Hersteller: {{ $booking->laptopConfig->old_manufacturer }}</p>
                        @endif
                    @else
                        <p class="mt-2 text-sm text-slate-500">
                            Noch keine Angaben. Bitte erfassen Sie, welche Software auf Ihrem Laptop installiert ist.
                        </p>
                    @endif

                    @if ($booking->laptopConfig?->additional_notes)
                        <p class="mt-3 text-xs font-medium text-slate-500">Zusätzliche Angaben:</p>
                        <p class="text-xs whitespace-pre-line text-slate-600">{{ $booking->laptopConfig->additional_notes }}</p>
                    @endif
                </div>
            @endif

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
                    <a href="{{ route('booking.ical', $booking) }}"
                        class="flex-1 rounded-md bg-white px-4 py-2.5 text-center text-sm font-medium text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50">
                        Zum Kalender hinzufügen
                    </a>
                @endif
            </div>
        </div>
    </div>
@endsection
