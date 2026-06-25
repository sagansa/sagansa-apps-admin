<x-filament-panels::page>
    {{-- Leaflet dimuat dari CDN (unpkg) agar tidak perlu mengubah build pipeline npm admin. --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIINfQ3ezLr+/xwB6U5jfnxkM4A3C8q6XwA=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <div class="space-y-4">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Menampilkan titik lokasi terbaru setiap pegawai yang telah mengirim lokasi
                (saat presensi, otomatis tiap ~2 jam, atau dari permintaan admin). Titik
                <span class="font-semibold">berwarna abu-abu</span> = data lebih dari 6 jam (kemungkinan perangkat mati / offline).
            </p>
            <x-filament::button wire:click="refreshData" icon="heroicon-o-arrow-path" size="sm">
                Segarkan
            </x-filament::button>
        </div>

        <div id="map" style="width: 100%; height: 70vh; min-height: 480px; border-radius: 0.5rem; z-index: 0;"></div>
    </div>

    <script>
        // Data lokasi disuntik dari server (TrackEmployeeLocation::getViewData).
        const SAGANSA_LOCATIONS = @json($locationsJson);

        document.addEventListener('DOMContentLoaded', function () {
            const el = document.getElementById('map');
            if (!el || typeof L === 'undefined') return;

            const map = L.map('map').setView([-2.5, 118], 5); // Indonesia by default

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a>',
                subdomains: ['a', 'b', 'c'],
            }).addTo(map);

            function freshIcon(stale) {
                return L.divIcon({
                    className: '',
                    html: '<span style="' +
                        'display:inline-block;width:18px;height:18px;border-radius:50%;' +
                        'border:3px solid #fff;box-shadow:0 0 4px rgba(0,0,0,.6);' +
                        'background:' + (stale ? '#9ca3af' : '#0ea5e9') + ';"></span>',
                    iconSize: [18, 18],
                    iconAnchor: [9, 9],
                });
            }

            const markers = [];
            SAGANSA_LOCATIONS.forEach(function (loc) {
                const marker = L.marker([loc.latitude, loc.longitude], { icon: freshIcon(loc.is_stale) }).addTo(map);
                const updated = loc.captured_at ? loc.captured_at : '-';
                const accuracy = loc.accuracy != null ? ('±' + Math.round(loc.accuracy) + ' m') : '-';
                const sourceLabel = loc.source === 'on_demand' ? 'Permintaan admin' : 'Otomatis (periodik)';
                marker.bindPopup(
                    '<div style="font-size:13px;line-height:1.4">' +
                    '<strong>' + escapeHtml(loc.name) + '</strong><br>' +
                    (loc.email ? escapeHtml(loc.email) + '<br>' : '') +
                    'Diperbarui: ' + escapeHtml(updated) + '<br>' +
                    'Akurasi: ' + accuracy + '<br>' +
                    'Sumber: ' + escapeHtml(sourceLabel) +
                    '</div>'
                );
                markers.push(marker);
            });

            // Sesuaikan batas tampilan agar semua marker terlihat (bila ada).
            if (markers.length > 0) {
                const group = L.featureGroup(markers);
                map.fitBounds(group.getBounds().pad(0.2));
            }

            // Auto-segarkan halaman setiap 60 detik (Livewire re-render memperbarui data).
            setInterval(function () {
                if (typeof Livewire !== 'undefined') {
                    Livewire.dispatch('$refresh');
                }
            }, 60000);
        });

        function escapeHtml(str) {
            if (str == null) return '';
            return String(str)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        }
    </script>
</x-filament-panels::page>
