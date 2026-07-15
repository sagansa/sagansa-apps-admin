<x-filament-panels::page>
    @push('styles')
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.css" rel="stylesheet">
    <style>
        .fc {
            font-family: inherit;
        }
        .fc-theme-standard td, .fc-theme-standard th {
            border-color: var(--tw-gray-300, #e5e7eb);
        }
        .fc-theme-standard .fc-scrollgrid {
            border: 1px solid var(--tw-gray-300, #e5e7eb);
            border-radius: 0.75rem;
            overflow: hidden;
        }
        .fc .fc-daygrid-day.fc-day-today {
            background-color: rgba(59, 130, 246, 0.1);
        }
        .fc .fc-col-header-cell {
            padding: 0.75rem 0.5rem;
            font-weight: 600;
            background-color: var(--tw-gray-50, #f9fafb);
        }
        .fc .fc-daygrid-day-number {
            padding: 0.5rem;
            font-size: 0.875rem;
        }
        .fc .fc-event {
            padding: 2px 4px;
            border-radius: 4px;
            font-size: 0.75rem;
            cursor: pointer;
            margin-bottom: 1px;
        }
        .fc .fc-daygrid-event-harness {
            margin-top: 1px;
        }
        .fc .fc-toolbar-title {
            font-size: 1.25rem;
            font-weight: 600;
        }
        .fc .fc-button-primary {
            background-color: var(--primary-500, #3b82f6);
            border-color: var(--primary-500, #3b82f6);
        }
        .fc .fc-button-primary:hover {
            background-color: var(--primary-600, #2563eb);
            border-color: var(--primary-600, #2563eb);
        }
        .fc .fc-button-primary:not(:disabled).fc-button-active {
            background-color: var(--primary-700, #1d4ed8);
            border-color: var(--primary-700, #1d4ed8);
        }
        .fc .fc-timegrid-slot {
            height: 2.5rem;
        }
        .fc .fc-timegrid-axis-cushion {
            font-size: 0.75rem;
            padding: 0 0.5rem;
        }
        .calendar-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1rem;
            padding: 1rem;
            background: var(--tw-gray-50, #f9fafb);
            border-radius: 0.75rem;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }
        .legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
        .legend-bg {
            width: 24px;
            height: 12px;
            border-radius: 2px;
            opacity: 0.7;
        }
        .event-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        .event-modal-content {
            background: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            max-width: 400px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        .event-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }
        .event-modal-title {
            font-size: 1.125rem;
            font-weight: 600;
        }
        .event-modal-close {
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 0.25rem;
        }
        .event-modal-close:hover {
            background: var(--tw-gray-100, #f3f4f6);
        }
        .event-detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--tw-gray-100, #f3f4f6);
        }
        .event-detail-label {
            color: var(--tw-gray-500, #6b7280);
            font-size: 0.875rem;
        }
        .event-detail-value {
            font-weight: 500;
            font-size: 0.875rem;
        }
    </style>
    @endpush

    {{-- Filters --}}
    <div class="mb-4 flex flex-wrap gap-4 items-center">
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Filter:</span>

        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" wire:model.live="showPresence" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            <span class="legend-dot" style="background-color: #22c55e;"></span>
            <span class="text-sm">Presensi</span>
        </label>

        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" wire:model.live="showLeave" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            <span class="legend-bg" style="background-color: #ca8a04;"></span>
            <span class="text-sm">Cuti</span>
        </label>

        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" wire:model.live="showSalary" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            <span class="legend-dot" style="background-color: #3b82f6;"></span>
            <span class="text-sm">Daily Salary</span>
        </label>

        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" wire:model.live="showClosing" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            <span class="legend-dot" style="background-color: #a855f7;"></span>
            <span class="text-sm">Closing Store</span>
        </label>

        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" wire:model.live="showStock" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            <span class="legend-dot" style="background-color: #f97316;"></span>
            <span class="text-sm">Stok Gudang</span>
        </label>

        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" wire:model.live="showAsset" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            <span class="legend-dot" style="background-color: #06b6d4;"></span>
            <span class="text-sm">Asset Check</span>
        </label>
    </div>

    {{-- Calendar --}}
    <div id="hrd-calendar" wire:ignore wire:poll.60s="loadEvents"></div>

    {{-- Legend --}}
    <div class="calendar-legend">
        <div class="legend-item">
            <span class="legend-dot" style="background-color: #22c55e;"></span>
            <span>Presensi Tepat Waktu</span>
        </div>
        <div class="legend-item">
            <span class="legend-dot" style="background-color: #eab308;"></span>
            <span>Presensi Terlambat</span>
        </div>
        <div class="legend-item">
            <span class="legend-dot" style="background-color: #ef4444;"></span>
            <span>Tidak Absen</span>
        </div>
        <div class="legend-item">
            <span class="legend-bg" style="background-color: #ca8a04;"></span>
            <span>Cuti Pending</span>
        </div>
        <div class="legend-item">
            <span class="legend-bg" style="background-color: #16a34a;"></span>
            <span>Cuti Disetujui</span>
        </div>
        <div class="legend-item">
            <span class="legend-dot" style="background-color: #3b82f6;"></span>
            <span>Daily Salary</span>
        </div>
        <div class="legend-item">
            <span class="legend-dot" style="background-color: #a855f7;"></span>
            <span>Closing Store</span>
        </div>
        <div class="legend-item">
            <span class="legend-dot" style="background-color: #f97316;"></span>
            <span>Stok Gudang</span>
        </div>
        <div class="legend-item">
            <span class="legend-dot" style="background-color: #06b6d4;"></span>
            <span>Asset Check</span>
        </div>
    </div>

    {{-- Event Detail Modal --}}
    @if($selectedEvent)
    <div class="event-modal" wire:click="closeEventModal">
        <div class="event-modal-content" x-on:click.stop>
            <div class="event-modal-header">
                <div class="event-modal-title" id="modal-title"></div>
                <button class="event-modal-close" wire:click="closeEventModal">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
            <div id="modal-content"></div>
        </div>
    </div>
    @endif

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.js"></script>
    <script>
        let calendar;

        function initCalendar() {
            const calendarEl = document.getElementById('hrd-calendar');
            if (!calendarEl) return;

            // Get initial events from Livewire
            const initialEvents = @js($events);

            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'id',
                height: 'auto',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                buttonText: {
                    today: 'Hari Ini',
                    month: 'Bulan',
                    week: 'Minggu',
                    day: 'Hari'
                },
                events: initialEvents,
                eventClick: function(info) {
                    showEventDetail(info.event);
                },
                dateClick: function(info) {
                    // Optional: show all events for that day
                },
                eventDidMount: function(info) {
                    // Add tooltip
                    info.el.title = info.event.title;
                },
                dayMaxEvents: 3,
                moreLinkText: function(n) {
                    return '+' + n + ' lainnya';
                }
            });

            calendar.render();
        }

        function updateCalendarEvents(events) {
            if (calendar) {
                calendar.removeAllEvents();
                calendar.addEventSource(events);
            }
        }

        function showEventDetail(event) {
            const props = event.extendedProps;
            const type = props.type;

            let content = '';
            let title = event.title;

            switch(type) {
                case 'presence':
                    content = `
                        <div class="event-detail-row">
                            <span class="event-detail-label">Karyawan</span>
                            <span class="event-detail-value">${props.employee}</span>
                        </div>
                        <div class="event-detail-row">
                            <span class="event-detail-label">Toko</span>
                            <span class="event-detail-value">${props.store}</span>
                        </div>
                        <div class="event-detail-row">
                            <span class="event-detail-label">Check In</span>
                            <span class="event-detail-value">${props.check_in}</span>
                        </div>
                        <div class="event-detail-row">
                            <span class="event-detail-label">Check Out</span>
                            <span class="event-detail-value">${props.check_out}</span>
                        </div>
                        <div class="event-detail-row">
                            <span class="event-detail-label">Status</span>
                            <span class="event-detail-value">${props.status}</span>
                        </div>
                    `;
                    break;

                case 'leave':
                    content = `
                        <div class="event-detail-row">
                            <span class="event-detail-label">Karyawan</span>
                            <span class="event-detail-value">${props.employee}</span>
                        </div>
                        <div class="event-detail-row">
                            <span class="event-detail-label">Jenis</span>
                            <span class="event-detail-value">${props.reason}</span>
                        </div>
                        <div class="event-detail-row">
                            <span class="event-detail-label">Status</span>
                            <span class="event-detail-value">${props.status}</span>
                        </div>
                        ${props.notes ? `
                        <div class="event-detail-row">
                            <span class="event-detail-label">Catatan</span>
                            <span class="event-detail-value">${props.notes}</span>
                        </div>
                        ` : ''}
                    `;
                    break;

                case 'daily_salary':
                    content = `
                        <div class="event-detail-row">
                            <span class="event-detail-label">Karyawan</span>
                            <span class="event-detail-value">${props.employee}</span>
                        </div>
                        <div class="event-detail-row">
                            <span class="event-detail-label">Toko</span>
                            <span class="event-detail-value">${props.store}</span>
                        </div>
                        <div class="event-detail-row">
                            <span class="event-detail-label">Jumlah</span>
                            <span class="event-detail-value">Rp ${new Intl.NumberFormat('id-ID').format(props.amount)}</span>
                        </div>
                        <div class="event-detail-row">
                            <span class="event-detail-label">Status</span>
                            <span class="event-detail-value">${props.status}</span>
                        </div>
                    `;
                    break;

                case 'closing_store':
                    content = `
                        <div class="event-detail-row">
                            <span class="event-detail-label">Toko</span>
                            <span class="event-detail-value">${props.store}</span>
                        </div>
                        <div class="event-detail-row">
                            <span class="event-detail-label">Total</span>
                            <span class="event-detail-value">Rp ${new Intl.NumberFormat('id-ID').format(props.amount)}</span>
                        </div>
                        <div class="event-detail-row">
                            <span class="event-detail-label">Status</span>
                            <span class="event-detail-value">${props.status}</span>
                        </div>
                    `;
                    break;

                case 'storage_stock':
                    content = `
                        <div class="event-detail-row">
                            <span class="event-detail-label">Toko</span>
                            <span class="event-detail-value">${props.store}</span>
                        </div>
                        <div class="event-detail-row">
                            <span class="event-detail-label">Status</span>
                            <span class="event-detail-value">${props.status}</span>
                        </div>
                    `;
                    break;

                case 'asset_check':
                    content = `
                        <div class="event-detail-row">
                            <span class="event-detail-label">Aset</span>
                            <span class="event-detail-value">${props.asset}</span>
                        </div>
                        <div class="event-detail-row">
                            <span class="event-detail-label">Diperiksa Oleh</span>
                            <span class="event-detail-value">${props.checked_by}</span>
                        </div>
                        <div class="event-detail-row">
                            <span class="event-detail-label">Status</span>
                            <span class="event-detail-value">${props.status}</span>
                        </div>
                    `;
                    break;
            }

            document.getElementById('modal-title').textContent = title;
            document.getElementById('modal-content').innerHTML = content;
            @this.set('selectedEvent', JSON.stringify(props));
        }
    </script>
    @endpush

    <script>
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                initCalendar();
            }, 100);
        });

        // Reinitialize calendar when Livewire updates the DOM
        document.addEventListener('livewire:updated', function() {
            setTimeout(() => {
                if (calendar) {
                    calendar.destroy();
                }
                initCalendar();
            }, 200);
        });
    </script>
</x-filament-panels::page>
