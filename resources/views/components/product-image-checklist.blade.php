@php
    $allImages = ($images ?? collect())->map(fn($img) => [
        'id' => $img->id,
        'url' => $img->getImageUrl(),
        'product_name' => $img->product?->name ?? 'Produk',
    ])->values()->toArray();
@endphp

<div
    x-data="{
        selected: $wire.$entangle('data.selected_image_ids'),
        allImages: {{ json_encode($allImages) }},
    }"
>
    <template x-if="allImages.length === 0">
        <p class="text-gray-500 text-sm py-4">Tidak ada gambar tersedia.</p>
    </template>

    <template x-if="allImages.length > 0">
        <div class="grid grid-cols-4 sm:grid-cols-5 md:grid-cols-6 gap-3">
            <template x-for="(img, idx) in allImages" :key="img.id">
                <label
                    class="relative flex flex-col items-center rounded-lg overflow-hidden border-2 cursor-pointer transition-all duration-150"
                    :class="(selected || []).includes(Number(img.id)) ? 'border-[#C6A96B] ring-2 ring-[#C6A96B]/30' : 'border-gray-700 hover:border-gray-500'"
                    style="aspect-ratio: 1;"
                >
                    <input
                        type="checkbox"
                        :value="img.id"
                        x-model="selected"
                        class="absolute top-1 right-1 z-10 w-4 h-4 rounded accent-[#C6A96B]"
                    />
                    <img
                        :src="img.url"
                        :alt="img.product_name"
                        class="w-full h-full object-cover"
                        loading="lazy"
                    />
                    <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent px-1.5 pb-1 pt-4">
                        <p class="text-white text-[10px] leading-tight truncate" x-text="img.product_name"></p>
                    </div>
                </label>
            </template>
        </div>
    </template>
</div>
