<x-filament-panels::page>
    <div class="fi-page-space-y space-y-4">

        {{-- Tujuan halaman --}}
        <x-filament::section>
            <x-slot:heading>
                Token untuk mengakses API superadmin
            </x-slot:heading>
            <x-slot:description>
                Token ini dipakai aplikasi StatementX (esta.sagansa.id) untuk menarik
                presensi, stok, dan penjualan dari superadmin.sagansa.id lewat header
                <code>Authorization: Bearer &lt;token&gt;</code>. Token tersimpan di DB
                auth terpusat (sagansa_user) dan tidak pernah bisa dilihat lagi setelah
                dibuat — simpan di tempat aman.
            </x-slot:description>
        </x-filament::section>

        {{-- Plaintext token baru — tampil sekali --}}
        @if ($plainToken)
            <x-filament::notification
                status="success"
                icon="heroicon-m-check-circle"
            >
                <x-slot:title>
                    Token berhasil dibuat — salin sekarang
                </x-slot:title>
                <x-slot:description>
                    Token <strong>tidak akan ditampilkan lagi</strong> setelah Anda
                    menyembunyikan pesan ini.
                </x-slot:description>
            </x-filament::notification>

            <div class="fi-section">
                <div class="fi-section-content">
                    <code
                        class="block w-full break-all rounded-lg bg-gray-100 px-4 py-3 font-mono text-sm dark:bg-gray-900 select-all"
                    >{{ $plainToken }}</code>
                    <div class="mt-3">
                        <x-filament::button
                            color="gray"
                            wire:click="hideToken"
                        >
                            Sembunyikan
                        </x-filament::button>
                    </div>
                </div>
            </div>
        @endif

        {{-- Form generate --}}
        <x-filament::section>
            <x-slot:heading>
                Buat token baru
            </x-slot:heading>
            <x-slot:description>
                Beri nama yang jelas (mis. peruntukan aplikasinya) supaya mudah
                dikenali saat mau mencabutnya.
            </x-slot:description>

            <x-slot:content>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="flex-1">
                        <x-filament::input.wrapper
                            label="Nama token"
                            :invalid="!!$errors->first('tokenName')"
                        >
                            <x-filament::input
                                type="text"
                                wire:model="tokenName"
                                placeholder="mis. esta-superadmin"
                                autocomplete="off"
                            />
                        </x-filament::input.wrapper>
                        @error('tokenName')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <x-filament::button
                            wire:click="createToken"
                            wire:loading.attr="disabled"
                        >
                            Generate token
                        </x-filament::button>
                    </div>
                </div>
            </x-slot:content>
        </x-filament::section>

        {{-- Daftar token --}}
        <x-filament::section>
            <x-slot:heading>
                Token aktif Anda ({{ count($tokens) }})
            </x-slot:heading>
            <x-slot:description>
                Token milik user lain tidak terlihat di sini. Mencabut token
                langsung menghentikan akses pemakainya.
            </x-slot:description>

            <x-slot:content>
                @if (count($tokens) === 0)
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Belum ada token. Buat token pertama dengan form di atas.
                    </p>
                @else
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 dark:text-gray-400">
                                <th class="py-2 pr-4 font-medium">Nama</th>
                                <th class="py-2 pr-4 font-medium">Dibuat</th>
                                <th class="py-2 pr-4 font-medium">Terakhir dipakai</th>
                                <th class="py-2 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tokens as $token)
                                <tr class="border-t border-gray-200 dark:border-gray-700">
                                    <td class="py-2 pr-4 font-medium">{{ $token['name'] }}</td>
                                    <td class="py-2 pr-4 text-gray-500 dark:text-gray-400">
                                        {{ $token['created_at'] ?? '—' }}
                                    </td>
                                    <td class="py-2 pr-4 text-gray-500 dark:text-gray-400">
                                        {{ $token['last_used_at'] ?? 'belum pernah' }}
                                    </td>
                                    <td class="py-2 text-right">
                                        <x-filament::button
                                            color="danger"
                                            size="sm"
                                            wire:click="revokeToken({{ $token['id'] }})"
                                            wire:confirm="Cabut token ini? Aplikasi yang memakainya langsung kehilangan akses."
                                        >
                                            Cabut
                                        </x-filament::button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </x-slot:content>
        </x-filament::section>

    </div>
</x-filament-panels::page>
