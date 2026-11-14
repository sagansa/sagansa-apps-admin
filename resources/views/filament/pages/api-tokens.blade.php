<x-filament-panels::page>
    {{--
        Catatan tampilan: class Tailwind utilitas di blade kustom TIDAK tersedia
        di CSS Filament yang ter-compile (terbukti: sm:flex-row/space-y-4/flex-1
        nihil di public/css/filament/filament/app.css). Semua layout halaman ini
        memakai CSS mentah ter-scope di bawah + komponen Filament native
        (section/callout/button) agar konsisten di tema gelap & terang.
    --}}
    <style>
        .api-tokens-page > * + * { margin-top: 1rem; }

        .api-tokens-form-row,
        .api-tokens-reveal-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        .api-tokens-form-row { align-items: flex-end; }
        .api-tokens-reveal-row { align-items: center; }

        .api-tokens-form-field { flex: 1 1 320px; }

        .api-tokens-form-label {
            display: block;
            margin-bottom: 0.375rem;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .api-tokens-token-input {
            flex: 1 1 320px;
            width: 100%;
            padding: 0.5rem 0.75rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 0.875rem;
            color: inherit;
            background: rgba(127, 127, 127, 0.08);
            border: 1px solid rgba(127, 127, 127, 0.4);
            border-radius: 0.5rem;
            cursor: text;
        }
        .api-tokens-token-input:focus-visible {
            outline: 2px solid rgba(127, 127, 127, 0.5);
            outline-offset: 1px;
        }

        .api-tokens-form-error {
            margin-top: 0.375rem;
            font-size: 0.875rem;
            color: #dc2626;
        }
    </style>

    <div class="api-tokens-page">

        {{-- Tujuan halaman --}}
        <x-filament::section description="Token ini dipakai aplikasi StatementX (esta.sagansa.id) untuk menarik presensi, stok, dan penjualan dari superadmin.sagansa.id lewat header Authorization: Bearer <token>. Token tersimpan di DB auth terpusat (sagansa_user) dan tidak pernah bisa dilihat lagi setelah dibuat — simpan di tempat aman.">
            <x-slot name="heading">
                Token untuk mengakses API superadmin
            </x-slot>
        </x-filament::section>

        {{-- Plaintext token baru — tampil sekali --}}
        @if ($plainToken)
            <x-filament::section x-data="{ plainToken: @js($plainToken) }">
                <x-slot name="heading">
                    Token berhasil dibuat — salin sekarang
                </x-slot>
                <x-slot name="description">
                    Token <strong>tidak akan ditampilkan lagi</strong> setelah Anda menyembunyikan blok ini.
                </x-slot>

                <div class="api-tokens-reveal-row">
                    <input
                        type="text"
                        readonly
                        value="{{ $plainToken }}"
                        class="api-tokens-token-input"
                        x-ref="tokenInput"
                        x-on:click="$refs.tokenInput.select()"
                    />
                    <x-filament::button
                        icon="heroicon-o-clipboard-document"
                        x-on:click="navigator.clipboard.writeText(plainToken)"
                    >
                        Salin
                    </x-filament::button>
                    <x-filament::button
                        color="gray"
                        wire:click="hideToken"
                    >
                        Sembunyikan
                    </x-filament::button>
                </div>

                <x-filament::callout
                    color="warning"
                    icon="heroicon-o-exclamation-triangle"
                    heading="Hanya sekali ini"
                >
                    Simpan token di tempat aman sekarang. Jika hilang, cabut token
                    ini dari daftar di bawah lalu buat token baru.
                </x-filament::callout>
            </x-filament::section>
        @endif

        {{-- Form generate --}}
        <x-filament::section description="Beri nama yang jelas (mis. peruntukan aplikasinya) supaya mudah dikenali saat mau mencabutnya.">
            <x-slot name="heading">
                Buat token baru
            </x-slot>

            <div class="api-tokens-form-row">
                <div class="api-tokens-form-field">
                    <label class="api-tokens-form-label" for="api-tokens-name">
                        Nama token
                    </label>
                    <x-filament::input.wrapper :valid="! $errors->first('tokenName')">
                        <x-filament::input
                            type="text"
                            id="api-tokens-name"
                            wire:model="tokenName"
                            placeholder="mis. esta-superadmin"
                            autocomplete="off"
                        />
                    </x-filament::input.wrapper>
                    @error('tokenName')
                        <p class="api-tokens-form-error">{{ $message }}</p>
                    @enderror
                </div>

                <x-filament::button
                    wire:click="createToken"
                    wire:loading.attr="disabled"
                >
                    Generate token
                </x-filament::button>
            </div>
        </x-filament::section>

        {{-- Daftar token — tabel native Filament (pagination, sort, aksi baris) --}}
        {{ $this->table }}

    </div>
</x-filament-panels::page>
