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

            {{-- Benötigte Software (Autovervollständigung aus dem Katalog) --}}
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200"
                x-data="{
                    tags: @js($selectedNames),
                    catalog: @js($suggestions),
                    query: '',
                    open: false,
                    highlight: -1,
                    get filtered() {
                        const q = this.query.trim().toLowerCase();
                        const chosen = this.tags.map(t => t.toLowerCase());
                        return this.catalog
                            .filter(n => !chosen.includes(n.toLowerCase()))
                            .filter(n => q === '' ? true : n.toLowerCase().includes(q))
                            .slice(0, 8);
                    },
                    add(name) {
                        name = (name || '').trim();
                        if (!name) return;
                        if (!this.tags.some(t => t.toLowerCase() === name.toLowerCase())) {
                            this.tags.push(name);
                        }
                        this.query = ''; this.open = false; this.highlight = -1;
                    },
                    addCurrent() {
                        if (this.highlight >= 0 && this.filtered[this.highlight]) {
                            this.add(this.filtered[this.highlight]);
                        } else { this.add(this.query); }
                    },
                    move(d) {
                        const max = this.filtered.length - 1;
                        if (max < 0) return;
                        this.highlight = Math.min(max, Math.max(0, this.highlight + d));
                        this.open = true;
                    },
                    remove(i) { this.tags.splice(i, 1); }
                }">
                <h2 class="text-lg font-semibold text-slate-900">Benötigte Software</h2>

                {{-- Standard-Software: bereits auf jedem Gerät enthalten --}}
                @php [$introHeading, $introBody] = \App\Models\Setting::splitHeading($softwareIntro); @endphp
                @if ($introHeading || $introBody)
                    <div class="mt-3 flex gap-2 rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800">
                        <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                        </svg>
                        <span>
                            @if ($introHeading)<span class="font-semibold">{{ $introHeading }}</span>@endif
                            @if ($introBody)<span class="mt-1 block">{!! nl2br(e($introBody)) !!}</span>@endif
                        </span>
                    </div>
                @endif

                {{-- Softwarecenter: eigenständige Installation nach Bedarf --}}
                @php [$centerHeading, $centerBody] = \App\Models\Setting::splitHeading($softwareCenterText); @endphp
                @if ($centerHeading || $centerBody || count($softwareCenterPrograms))
                    <div class="mt-3 flex gap-2 rounded-md border border-blue-200 bg-blue-50 px-3 py-2 text-sm text-blue-800">
                        <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                        </svg>
                        <span>
                            @if ($centerHeading)<span class="font-semibold">{{ $centerHeading }}</span>@endif
                            @if ($centerBody)<span class="mt-1 block">{!! nl2br(e($centerBody)) !!}</span>@endif
                            @if (count($softwareCenterPrograms))
                                <span class="mt-2 flex flex-wrap gap-1.5">
                                    @foreach ($softwareCenterPrograms as $sc)
                                        <span class="rounded-full bg-white px-2.5 py-0.5 text-xs font-medium text-blue-700 ring-1 ring-blue-200">{{ $sc }}</span>
                                    @endforeach
                                </span>
                            @endif
                        </span>
                    </div>
                @endif

                {{-- Ihre Arbeitsumgebung: Einleitung direkt über dem Eingabefeld --}}
                @php [$workHeading, $workBody] = \App\Models\Setting::splitHeading($softwareWarning); @endphp
                @if ($workHeading || $workBody)
                    <div class="mt-4">
                        @if ($workHeading)<h3 class="text-sm font-semibold text-slate-900">{{ $workHeading }}</h3>@endif
                        @if ($workBody)<p class="mt-1 text-sm text-slate-500">{!! nl2br(e($workBody)) !!}</p>@endif
                    </div>
                @endif

                {{-- Ausgewählte Programme als Chips --}}
                <div class="mt-4 flex flex-wrap gap-2">
                    <template x-for="(tag, i) in tags" :key="i">
                        <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-3 py-1 text-sm text-blue-700 ring-1 ring-blue-200">
                            <span x-text="tag"></span>
                            <button type="button" @click="remove(i)" class="text-blue-400 hover:text-blue-700" aria-label="Entfernen">&times;</button>
                        </span>
                    </template>
                    <span x-show="tags.length === 0" class="text-sm text-slate-400">Noch keine Software ausgewählt.</span>
                </div>

                {{-- Eingabe mit Vorschlägen --}}
                <div class="relative mt-3" @click.outside="open = false">
                    <input type="text" x-model="query"
                        @focus="open = true"
                        @keydown.enter.prevent="addCurrent()"
                        @keydown.arrow-down.prevent="move(1)"
                        @keydown.arrow-up.prevent="move(-1)"
                        @keydown.escape="open = false"
                        placeholder="z. B. Adobe Acrobat Reader"
                        autocomplete="off"
                        class="block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">

                    <ul x-show="open && filtered.length" x-cloak
                        class="absolute z-10 mt-1 max-h-56 w-full overflow-auto rounded-md bg-white py-1 shadow-lg ring-1 ring-slate-200">
                        <template x-for="(name, idx) in filtered" :key="name">
                            <li @click="add(name)" @mouseenter="highlight = idx"
                                :class="idx === highlight ? 'bg-blue-50' : ''"
                                class="cursor-pointer px-3 py-2 text-sm text-slate-700"
                                x-text="name"></li>
                        </template>
                    </ul>
                </div>
                <p class="mt-2 text-xs text-slate-400">
                    Nicht in der Liste? Einfach eintippen und mit <kbd class="rounded bg-slate-100 px-1">Enter</kbd> hinzufügen.
                </p>

                {{-- Versteckte Felder für das Absenden --}}
                <template x-for="(tag, i) in tags" :key="'hidden-' + i">
                    <input type="hidden" name="software_names[]" :value="tag">
                </template>
            </div>

            {{-- Zusätzliche Angaben (Freitext) --}}
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <label for="additional_notes" class="block text-lg font-semibold text-slate-900">
                    Zusätzliche Angaben (optional)
                </label>
                <p class="mt-1 text-sm text-slate-500">
                    Sonstige Hinweise für die IT, z. B. besondere Einstellungen, Peripherie oder Anmerkungen zum Gerät.
                </p>
                <textarea id="additional_notes" name="additional_notes" rows="4" maxlength="2000"
                    class="mt-3 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                    placeholder="z. B. zweiter Monitor wird benötigt, Dockingstation vorhanden …">{{ old('additional_notes', $booking->laptopConfig?->additional_notes) }}</textarea>
                @error('additional_notes')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
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
