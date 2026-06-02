@extends('layouts.app')

@section('title', 'Software erfassen')

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-slate-900">Angaben zu Ihrem Laptop</h1>
            <p class="mt-1 text-sm text-slate-500">
                Damit Ihr neues Gerät richtig eingerichtet werden kann, geben Sie bitte an,
                welche Software auf Ihrem <strong>aktuellen</strong> Laptop installiert ist.
            </p>
        </div>

        <form method="POST" action="{{ route('config.update', $booking) }}" class="space-y-6">
            @csrf

            {{-- Gerät --}}
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-lg font-semibold text-slate-900">Gerät</h2>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">PC-Nummer (aktuelles Gerät)</label>
                        <input type="text" value="{{ $booking->employee->pc_nummer }}" disabled
                            class="mt-1 block w-full rounded-md border-slate-200 bg-slate-50 text-slate-500 sm:text-sm">
                        <p class="mt-1 text-xs text-slate-400">Wird automatisch übernommen.</p>
                    </div>

                    <div>
                        <label for="manufacturer" class="block text-sm font-medium text-slate-700">Hersteller (optional)</label>
                        <select id="manufacturer" name="manufacturer"
                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="">– bitte wählen –</option>
                            @foreach ($manufacturers as $m)
                                <option value="{{ $m }}" @selected(old('manufacturer', $booking->laptopConfig?->old_manufacturer) === $m)>{{ $m }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Software aus dem Katalog --}}
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-lg font-semibold text-slate-900">Installierte Software</h2>
                <p class="mt-1 text-sm text-slate-500">Wählen Sie die Programme, die Sie weiterhin benötigen.</p>

                @php
                    $selected = old('software', $selectedCatalogIds);
                    $standard = $catalog->where('is_standard', true);
                    $weitere = $catalog->where('is_standard', false);
                @endphp

                @foreach (['Standardsoftware' => $standard, 'Weitere Software' => $weitere] as $gruppe => $items)
                    @if ($items->isNotEmpty())
                        <p class="mt-4 text-xs font-medium uppercase tracking-wide text-slate-400">{{ $gruppe }}</p>
                        <div class="mt-2 grid gap-2 sm:grid-cols-2">
                            @foreach ($items as $software)
                                <label class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm text-slate-700 hover:bg-slate-50">
                                    <input type="checkbox" name="software[]" value="{{ $software->id }}"
                                        @checked(in_array($software->id, $selected))
                                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                    {{ $software->name }}
                                </label>
                            @endforeach
                        </div>
                    @endif
                @endforeach
            </div>

            {{-- Spezialsoftware --}}
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-lg font-semibold text-slate-900">Spezial-/Fachsoftware</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Programme, die oben nicht aufgeführt sind – <strong>ein Programm pro Zeile</strong>.
                </p>
                <textarea name="custom_software" rows="4"
                    placeholder="z. B. Fachverfahren XY&#10;Spezialtool Z"
                    class="mt-3 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">{{ old('custom_software', $customSoftware) }}</textarea>
            </div>

            <div class="flex gap-3">
                <a href="{{ route('booking.show', $booking) }}"
                    class="flex-1 rounded-md bg-slate-100 px-4 py-2.5 text-center text-sm font-medium text-slate-700 hover:bg-slate-200">
                    Abbrechen
                </a>
                <button type="submit"
                    class="flex-1 rounded-md bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                    Speichern
                </button>
            </div>
        </form>
    </div>
@endsection
