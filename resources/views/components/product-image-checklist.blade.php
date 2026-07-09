@php
    $statePath = $component->getStatePath();
    $images = $component->getViewData()['images'] ?? collect();
@endphp

<div
    x-data="{
        selected: $wire.entangle('data.{{ $statePath }}').live,
        selectedProductIds: $wire.entangle('data.selected_product_ids').live,
        get visibleImages() {
            if (!this.selectedProductIds || this.selectedProductIds.length === 0) return [];
            return {{ Js::from($images->map(fn($img) => [
                'id' => $img->id,
                'product_id' => $img->product_id,
                'url' => $img->getImageUrl(),
                'product_name' => $img->product?->name ?? 'Produk',
            ])->values()->toArray()) }}.filter(
                img => this.selectedProductIds.includes(img.product_id)
            );
        }
    }"
    class="space-y-3"
>
    <template x-if="!selectedProductIds || selectedProductIds.length === 0">
        <p class="text-gray-500 text-sm py-4">
            Pilih produk terlebih dahulu untuk melihat gambar.
        </p>
    </template>

    <template x-if="selectedProductIds && selectedProductIds.length > 0 && visibleImages.length === 0">
        <p class="text-gray-500 text-sm py-4">
            Tidak ada gambar pada produk yang dipilih.
        </p>
    </template>

    <div
        x-show="visibleImages.length > 0"
        class="grid grid-cols-4 sm:grid-cols-5 md:grid-cols-6 gap-3"
    >
        <template x-for="(img, idx) in visibleImages" :key="img.id">
            <label
                class="relative flex flex-col items-center rounded-lg overflow-hidden border-2 cursor-pointer transition-all duration-150"
                :class="selected.includes(img.id) ? 'border-[#C6A96B] ring-2 ring-[#C6A96B]/30' : 'border-gray-700 hover:border-gray-500'"
                style="aspect-ratio: 1;"
            >
                <input
                    type="checkbox"
                    :value="img.id"
                    x-model="selected"
                    class="absolute top-1 right-1 z-10 w-4 h-4 rounded accent-[#C6A96B]"
                    :checked="selected.includes(img.id)"
                />
                <img
                    :src="img.url"
                    :alt="img.product_name"
                    class="w-full h-full object-cover"
                    loading="lazy"
                    onerror="this.parentElement.innerHTML='<div class=\\'flex items-center justify-center w-full h-full text-gray-500 text-xs p-1\\'>No Img</div>'"
                />
                <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent px-1.5 pb-1 pt-4">
                    <p class="text-white text-[10px] leading-tight truncate" x-text="img.product_name"></p>
                </div>
            </label>
        </template>
    </div>
</div>
