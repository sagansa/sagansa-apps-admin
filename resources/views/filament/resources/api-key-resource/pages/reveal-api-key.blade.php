<x-filament-panels::page>
    @if (filled($plainKey))
        <div class="space-y-6" x-data="{ plainKey: @js($plainKey) }">
            <x-filament::section>
                <x-slot name="heading">
                    {{ $keyName ?? 'API Key' }}
                </x-slot>
                <x-slot name="description">
                    Simpan key ini sekarang di tempat aman — key lengkap
                    <strong>tidak akan ditampilkan lagi</strong>.
                </x-slot>

                <div class="flex items-center gap-x-3">
                    <input
                        type="text"
                        readonly
                        value="{{ $plainKey }}"
                        class="block w-full font-mono text-sm rounded-lg border-gray-300 bg-gray-50 dark:bg-gray-900"
                        x-ref="keyInput"
                        x-on:click="$refs.keyInput.select()"
                    />
                    <x-filament::button
                        icon="heroicon-o-clipboard-document"
                        x-on:click="navigator.clipboard.writeText(plainKey)"
                    >
                        Salin
                    </x-filament::button>
                </div>

                <x-filament::callout
                    color="warning"
                    icon="heroicon-o-exclamation-triangle"
                    heading="Hanya sekali ini"
                >
                    Di database hanya tersimpan hash SHA-256. Jika key hilang,
                    buat key baru (rotasi).
                </x-filament::callout>
            </x-filament::section>

            <div>
                <x-filament::button
                    color="primary"
                    :url="route('filament.admin.resources.panel.api-keys.index')"
                    tag="a"
                >
                    Selesai — kembali ke daftar
                </x-filament::button>
            </div>
        </div>
    @endif
</x-filament-panels::page>
