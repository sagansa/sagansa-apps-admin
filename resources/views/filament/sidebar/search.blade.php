@use(Filament\Support\Icons\Heroicon)
@php
    // Bangun daftar flat semua item navigasi, termasuk child items di dalam
    // cluster. Cluster child resources TIDAK muncul di filament()->getNavigation()
    // (mereka self-exclude di registerNavigationItems() saat punya $cluster),
    // jadi kita enumerate terpisah via getClusters() + getClusteredComponents().
    //
    // Search harus mencocokkan KEDUA bahasa (id & en) terlepas dari locale
    // aktif. Karena label diambil via __() di getNavigationLabel(), kita
    // bangun flat list DUA KALI: sekali dengan locale 'id', sekali 'en',
    // lalu gabungkan label keduanya per item (dipasangkan via URL).

    $buildFlatItems = function (): array {
        $items = [];

        // 1. Item tingkat atas (resource/page non-cluster + cluster itu sendiri)
        foreach (filament()->getNavigation() as $group) {
            $groupLabel = $group->getLabel();
            foreach ($group->getItems() as $item) {
                $url = $item->getUrl();
                if (filled($url)) {
                    $items[$url] = [
                        'label' => $item->getLabel(),
                        'url' => $url,
                        'group' => $groupLabel,
                    ];
                }
            }
        }

        // 2. Child items tiap cluster (resource/page di dalam cluster)
        $panel = filament()->getCurrentOrDefaultPanel();
        foreach ($panel->getClusters() as $clusterClass) {
            try {
                $clusterLabel = $clusterClass::getNavigationLabel();
                $components = $panel->getClusteredComponents($clusterClass);
            } catch (\Throwable $e) {
                continue;
            }

            foreach ($components as $component) {
                try {
                    if (method_exists($component, 'shouldRegisterNavigation')
                        && ! $component::shouldRegisterNavigation()) {
                        continue;
                    }
                    if (method_exists($component, 'canAccess') && ! $component::canAccess()) {
                        continue;
                    }

                    foreach ($component::getNavigationItems() as $navItem) {
                        $url = $navItem->getUrl();
                        if (filled($url)) {
                            $items[$url] = [
                                'label' => $navItem->getLabel(),
                                'url' => $url,
                                'group' => $clusterLabel,
                            ];
                        }
                    }
                } catch (\Throwable $e) {
                    continue;
                }
            }
        }

        return $items;
    };

    // Bangun dengan locale aktif, lalu locale pasangan, gabungkan labelnya.
    $app = app();
    $originalLocale = $app->getLocale();

    $byUrl = $buildFlatItems();

    $otherLocale = ($originalLocale === 'id') ? 'en' : 'id';
    $app->setLocale($otherLocale);
    $otherItems = $buildFlatItems();
    $app->setLocale($originalLocale);

    // Gabungkan: tiap item punya array "labels" berisi label dari kedua locale.
    $flatItems = [];
    foreach ($byUrl as $url => $data) {
        $labels = array_values(array_unique(array_filter([
            $data['label'],
            $otherItems[$url]['label'] ?? null,
        ])));
        $flatItems[] = [
            'labels' => $labels,
            'label' => $data['label'], // label aktif untuk ditampilkan
            'url' => $url,
            'group' => $data['group'],
        ];
    }
@endphp

<div
    x-data="{
        query: '',

        get results() {
            const q = this.query.trim().toLowerCase()
            if (q === '') return []
            return @js($flatItems).filter((item) =>
                item.labels.some((label) => label.toLowerCase().includes(q))
            )
        },

        get isSearching() {
            return this.query.trim() !== ''
        },
    }"
    x-show="$store.sidebar.isOpen"
    x-init="
        document.addEventListener('keydown', (e) => {
            if (e.key === '/' && !['INPUT','TEXTAREA','SELECT'].includes(e.target.tagName)) {
                e.preventDefault()
                $refs.searchInput.focus()
            }
        })
    "
    x-effect="
        // Saat mencari: sembunyikan nav groups Filament.
        // Saat kosong: kembalikan.
        const groups = document.querySelector('.fi-sidebar-nav-groups')
        if (groups) {
            Array.from(groups.children).forEach((child) => {
                if (child.classList.contains('fi-sidebar-group')) {
                    child.style.display = isSearching ? 'none' : ''
                }
            })
        }
    "
    class="fi-sidebar-search px-3 py-2"
>
    <x-filament::input.wrapper
        :prefix-icon="Heroicon::MagnifyingGlass"
        inline-prefix
    >
        <input
            x-ref="searchInput"
            type="search"
            autocomplete="off"
            placeholder="{{ __('navigation.search_placeholder') }}"
            wire:ignore
            x-model="query"
            class="fi-input fi-input-has-inline-prefix"
        />
    </x-filament::input.wrapper>

    {{-- Daftar hasil pencarian (flat, semua item termasuk child cluster) --}}
    <ul
        x-show="isSearching"
        x-cloak
        class="fi-sidebar-search-results"
        style="list-style: none; padding: 0; margin-top: 0.5rem; display: flex; flex-direction: column; gap: 0.125rem;"
    >
        <template x-for="item in results" :key="item.url">
            <li>
                <a
                    :href="item.url"
                    x-on:click="query = ''"
                    class="fi-sidebar-search-result"
                    style="display: flex; flex-direction: column; padding: 0.5rem 0.75rem; border-radius: 0.375rem; gap: 0.125rem; text-decoration: none; color: inherit;"
                >
                    <span x-text="item.label" style="font-size: 0.875rem; font-weight: 500;"></span>
                    <span x-text="item.group" style="font-size: 0.75rem; opacity: 0.6;"></span>
                </a>
            </li>
        </template>
        <template x-if="results.length === 0">
            <li style="padding: 0.5rem 0.75rem; font-size: 0.875rem; opacity: 0.6;">
                {{ __('navigation.no_results') }}
            </li>
        </template>
    </ul>
</div>
