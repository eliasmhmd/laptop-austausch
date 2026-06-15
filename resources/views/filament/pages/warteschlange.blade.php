<x-filament-panels::page>
    <x-filament::tabs>
        <x-filament::tabs.item
            :active="$activeTab === 'offen'"
            :badge="$this->offenCount() ?: null"
            badge-color="warning"
            wire:click="setTab('offen')"
        >
            Offen
        </x-filament::tabs.item>

        <x-filament::tabs.item
            :active="$activeTab === 'bereit'"
            :badge="$this->bereitCount() ?: null"
            badge-color="success"
            wire:click="setTab('bereit')"
        >
            Bereit
        </x-filament::tabs.item>
    </x-filament::tabs>

    {{ $this->table }}
</x-filament-panels::page>
