<x-filament-panels::page>
    <div class="space-y-4">

        {{-- Tujuan halaman --}}
        <x-filament::section description="Token ini dipakai aplikasi StatementX (esta.sagansa.id) untuk menarik presensi, stok, dan penjualan dari superadmin.sagansa.id lewat header Authorization: Bearer <token>. Token tersimpan di DB auth terpusat (sagansa_user) dan tidak pernah bisa dilihat lagi setelah dibuat — simpan di tempat aman.">
            <x-slot:heading>
                Token untuk mengakses API superadmin
            </x-slot:heading>
        </x-filament::section>

        {{-- Plaintext token baru — tampil sekali --}}
        @if ($plainToken)
            <div class="fi-section rounded-xl border border-success-500/30 bg-success-500/5 p-4">
                <p class="font-bold text-success-600 dark:text-success-400">
                    Token berhasil dibuat — salin sekarang
                </p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Token <strong>tidak akan ditampilkan lagi</strong> setelah Anda menyembunyikan pesan ini.
                </p>
                <code
                    class="mt-3 block w-full select-all break-all rounded-lg bg-gray-100 px-4 py-3 font-mono text-sm dark:bg-gray-900"
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
        @endif

        {{-- Form generate --}}
        <x-filament::section description="Beri nama yang jelas (mis. peruntukan aplikasinya) supaya mudah dikenali saat mau mencabutnya.">
            <x-slot:heading>
                Buat token baru
            </x-slot:heading>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <label class="mb-1 block text-sm font-medium">
                        Nama token
                    </label>
                    <x-filament::input.wrapper :valid="! $errors->first('tokenName')">
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
        </x-filament::section>

        {{-- Daftar token — tabel native Filament (pagination, sort, aksi baris) --}}
        {{ $this->table }}

    </div>
</x-filament-panels::page>
