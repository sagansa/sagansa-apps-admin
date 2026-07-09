@php
    $allImages = ($images ?? collect())->map(fn($img) => [
        'id' => $img->id,
        'product_id' => $img->product_id,
        'url' => $img->getImageUrl(),
        'product_name' => $img->product?->name ?? 'Produk',
    ])->values()->toArray();
@endphp

<div
    x-data="{
        selected: $wire.$entangle('data.selected_image_ids'),
        selectedProductIds: $wire.$entangle('data.selected_product_ids'),
        allImages: {{ json_encode($allImages) }},
        get selectedArray() {
            return Array.isArray(this.selected) ? this.selected : [];
        },
        get selectedProductIdsArray() {
            return Array.isArray(this.selectedProductIds) ? this.selectedProductIds : [];
        },
        get filteredImages() {
            const ids = this.selectedProductIdsArray;
            if (ids.length === 0) return [];
            return this.allImages.filter(
                img => ids.map(Number).includes(Number(img.product_id))
            );
        },
        toggleImage(id) {
            const numId = Number(id);
            const arr = this.selectedArray;
            if (arr.includes(numId)) {
                this.selected = arr.filter(v => v !== numId);
            } else {
                this.selected = [...arr, numId];
            }
        }
    }"
>
    <template x-if="selectedProductIdsArray.length === 0">
        <p class="text-gray-500 text-sm py-4">Pilih produk terlebih dahulu untuk melihat gambar.</p>
    </template>

    <template x-if="selectedProductIdsArray.length > 0 && filteredImages.length === 0">
        <p class="text-gray-500 text-sm py-4">Tidak ada gambar pada produk yang dipilih.</p>
    </template>

    <template x-if="filteredImages.length > 0">
        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
            <template x-for="(img, idx) in filteredImages" :key="img.id">
                <label
                    :class="selectedArray.includes(Number(img.id)) ? 'border-[#C6A96B] ring-2 ring-[#C6A96B]/30' : 'border-gray-700 hover:border-gray-500'"
                    style="width: calc(16.666% - 7px); aspect-ratio: 1; position: relative; display: flex; flex-direction: column; align-items: center; border-radius: 8px; overflow: hidden; border-width: 2px; border-style: solid; cursor: pointer; transition: all 0.15s;"
                >
                    <input
                        type="checkbox"
                        :value="img.id"
                        :checked="selectedArray.includes(Number(img.id))"
                        @change="toggleImage(img.id)"
                        style="position: absolute; top: 4px; right: 4px; z-index: 10; width: 16px; height: 16px; border-radius: 4px; accent-color: #C6A96B;"
                    />
                    <img
                        :src="img.url"
                        :alt="img.product_name"
                        style="width: 100%; height: 100%; object-fit: cover;"
                        loading="lazy"
                    />
                    <div style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(to top, rgba(0,0,0,0.7), transparent); padding: 4px 6px 2px;">
                        <p style="color: white; font-size: 10px; line-height: 1.2; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" x-text="img.product_name"></p>
                    </div>
                </label>
            </template>
        </div>
    </template>
</div>
