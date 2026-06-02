{{--
    Wiederverwendbares Kalender-Raster.
    Erwartete Variablen:
      $calendar, $weeks, $selectedKw, $availableIds  (aus BuildsSlotCalendar)
      $filterRoute  – Routenname für den KW-Filter (z. B. 'booking.calendar')
      $selectRoute  – Routenname beim Klick auf ein freies Fenster
--}}

{{-- Legende --}}
<div class="mb-4 flex flex-wrap items-center gap-4 text-sm text-slate-600">
    <span class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-green-500"></span> verfügbar</span>
    <span class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-red-500"></span> belegt</span>
    <span class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-slate-400"></span> gesperrt</span>
</div>

{{-- Filter nach Kalenderwoche --}}
<div class="mb-6 flex flex-wrap gap-2">
    <a href="{{ route($filterRoute) }}"
        class="rounded-md px-3 py-1.5 text-sm font-medium {{ ! $selectedKw ? 'bg-blue-600 text-white' : 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50' }}">
        Alle Wochen
    </a>
    @foreach ($weeks as $week)
        <a href="{{ route($filterRoute, ['kw' => $week]) }}"
            class="rounded-md px-3 py-1.5 text-sm font-medium {{ $selectedKw === $week ? 'bg-blue-600 text-white' : 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50' }}">
            KW {{ $week }}
        </a>
    @endforeach
</div>

<div
    x-data="{
        available: @js($availableIds),
        isFree(id) { return this.available.includes(id) },
        async refresh() {
            try {
                const res = await fetch('{{ route('booking.availability', ['kw' => $selectedKw]) }}', { headers: { Accept: 'application/json' } })
                const data = await res.json()
                this.available = data.available
            } catch (e) { /* Netzwerkfehler ignorieren, nächster Tick versucht es erneut */ }
        },
        async select(id, url) {
            try {
                const res = await fetch('/api/slot/' + id, { headers: { Accept: 'application/json' } })
                const data = await res.json()
                if (data.available) {
                    window.location = url
                } else {
                    this.available = this.available.filter(x => x !== id)
                    alert('Dieses Zeitfenster wurde gerade vergeben. Bitte wählen Sie ein anderes.')
                }
            } catch (e) {
                window.location = url
            }
        },
        init() { setInterval(() => this.refresh(), 15000) }
    }"
    class="space-y-8"
>
    @forelse ($calendar as $week => $days)
        <section>
            <h2 class="mb-3 text-lg font-semibold text-slate-800">Kalenderwoche {{ $week }}</h2>
            <div class="space-y-3">
                @foreach ($days as $date => $slots)
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                        <p class="mb-3 text-sm font-medium text-slate-700">
                            {{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('l, d.m.Y') }}
                        </p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($slots as $slot)
                                @if ($slot->status === 'blocked')
                                    <span class="cursor-not-allowed rounded-md bg-slate-200 px-3 py-2 text-sm font-medium text-slate-400"
                                        title="Gesperrt">
                                        {{ \Illuminate\Support\Str::substr($slot->start_time, 0, 5) }}
                                    </span>
                                @else
                                    <button type="button"
                                        x-on:click="select({{ $slot->id }}, '{{ route($selectRoute, $slot) }}')"
                                        x-bind:disabled="! isFree({{ $slot->id }})"
                                        x-bind:class="isFree({{ $slot->id }})
                                            ? 'bg-green-500 text-white hover:bg-green-600 cursor-pointer'
                                            : 'bg-red-500 text-white opacity-70 cursor-not-allowed'"
                                        class="rounded-md px-3 py-2 text-sm font-semibold transition">
                                        {{ \Illuminate\Support\Str::substr($slot->start_time, 0, 5) }}
                                    </button>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @empty
        <p class="rounded-xl bg-white p-6 text-center text-sm text-slate-500 shadow-sm ring-1 ring-slate-200">
            Für diese Auswahl sind keine Zeitfenster vorhanden.
        </p>
    @endforelse
</div>
