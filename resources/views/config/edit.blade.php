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
                <p class="mt-1 text-sm text-slate-500">
                    Standardprogramme (Office, Browser …) sind bereits auf jedem Gerät.
                    Geben Sie hier nur <strong>zusätzlich</strong> benötigte Programme an.
                </p>

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
