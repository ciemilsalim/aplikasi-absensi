<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Kalender Pendidikan') }}
        </h2>
        <x-slot name="header">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Kalender Pendidikan') }}
            </h2>
        </x-slot>

        @push('styles')
            <!-- FullCalendar CSS -->
            <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
            <style>
                .fc-theme-standard .fc-scrollgrid {
                    border-color: #e2e8f0;
                }

                .dark .fc-theme-standard .fc-scrollgrid {
                    border-color: #334155;
                }

                .fc-theme-standard td,
                .fc-theme-standard th {
                    border-color: #e2e8f0;
                }

                .dark .fc-theme-standard td,
                .dark .fc-theme-standard th {
                    border-color: #334155;
                }

                .fc-col-header-cell-cushion {
                    color: #475569;
                    font-weight: 600;
                    padding: 12px 0 !important;
                }

                .dark .fc-col-header-cell-cushion {
                    color: #cbd5e1;
                }

                .fc-daygrid-day-number {
                    color: #64748b;
                    font-weight: 500;
                    font-size: 0.875rem;
                    padding: 8px !important;
                }

                .dark .fc-daygrid-day-number {
                    color: #94a3b8;
                }

                .fc-daygrid-event {
                    border-radius: 4px;
                    padding: 2px 4px;
                    font-size: 0.75rem;
                    border: none;
                    font-weight: 500;
                }

                .fc-event-title {
                    padding-left: 2px;
                }

                .fc-h-event .fc-event-main {
                    color: white;
                }

                .fc .fc-toolbar-title {
                    font-size: 1.25rem;
                    font-weight: 600;
                    color: #1e293b;
                }

                .dark .fc .fc-toolbar-title {
                    color: #f8fafc;
                }

                .fc .fc-button-primary {
                    background-color: #0284c7;
                    border-color: #0284c7;
                }

                .fc .fc-button-primary:hover {
                    background-color: #0369a1;
                    border-color: #0369a1;
                }

                .fc .fc-button-primary:disabled {
                    background-color: #7dd3fc;
                    border-color: #7dd3fc;
                }

                .fc .fc-day-today {
                    background-color: #f0f9ff !important;
                }

                .dark .fc .fc-day-today {
                    background-color: #0c4a6e !important;
                }

                .fc-event.holiday-event {
                    background-color: #ef4444;
                    border-color: #ef4444;
                }

                .fc-event.activity-event {
                    background-color: #10b981;
                    border-color: #10b981;
                }

                /* Cursor pointer pada event */
                .fc-event {
                    cursor: pointer;
                    transition: transform 0.1s ease;
                }

                .fc-event:hover {
                    transform: scale(1.02);
                    opacity: 0.9;
                }
            </style>
        @endpush

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

                <div class="mb-6 flex justify-between items-center">
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        {{ __('Kalender Pendidikan') }}
                    </h2>
                    <button x-data="" x-on:click.prevent="$dispatch('open-modal', 'import-calendar')"
                        class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 active:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="w-4 h-4 mr-2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                        </svg>
                        Import Excel
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Form Tambah Agenda -->
                    <div class="md:col-span-1">
                        <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Tambah Agenda</h3>
                                <form action="{{ route('admin.calendars.store') }}" method="POST">
                                    @csrf
                                    <div class="space-y-4">
                                        <div>
                                            <x-input-label for="title" :value="__('Judul Agenda')" />
                                            <x-text-input id="title" class="block mt-1 w-full" type="text" name="title"
                                                :value="old('title')" required />
                                            <x-input-error :messages="$errors->get('title')" class="mt-2" />
                                        </div>

                                        <div>
                                            <x-input-label for="start_date" :value="__('Tanggal Mulai')" />
                                            <x-text-input id="start_date" class="block mt-1 w-full" type="date"
                                                name="start_date" :value="old('start_date')" required />
                                            <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
                                        </div>

                                        <div>
                                            <x-input-label for="end_date" :value="__('Tanggal Selesai (Opsional)')" />
                                            <x-text-input id="end_date" class="block mt-1 w-full" type="date"
                                                name="end_date" :value="old('end_date')" />
                                            <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
                                        </div>

                                        <div>
                                            <x-input-label for="description" :value="__('Deskripsi')" />
                                            <textarea id="description" name="description"
                                                class="block mt-1 w-full border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 dark:focus:border-sky-600 focus:ring-sky-500 dark:focus:ring-sky-600 rounded-md shadow-sm"
                                                rows="3">{{ old('description') }}</textarea>
                                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                                        </div>

                                        <div class="flex items-center">
                                            <input id="is_holiday" type="checkbox" name="is_holiday" value="1"
                                                class="w-4 h-4 text-sky-600 bg-gray-100 border-gray-300 rounded focus:ring-sky-500 dark:focus:ring-sky-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                                                checked>
                                            <label for="is_holiday"
                                                class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Tandai
                                                sebagai Hari Libur</label>
                                        </div>

                                        <div class="flex justify-end">
                                            <x-primary-button>Simpan</x-primary-button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Daftar Agenda -->
                    <div class="md:col-span-2">
                        <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Agenda Interaktif
                                </h3>
                                <!-- Container untuk Kalender -->
                                <div id="calendar"
                                    class="mt-4 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 min-h-[500px]">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Import -->
        <x-modal name="import-calendar" focusable>
            <form method="post" action="{{ route('admin.calendars.import') }}" class="p-6"
                enctype="multipart/form-data">
                @csrf

                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ __('Import Data Agenda') }}
                </h2>

                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('Silakan unggah file Excel (.xlsx, .xls) yang berisi data agenda. Pastikan format kolom sesuai: title, start_date, end_date, description, is_holiday.') }}
                </p>

                <div class="mt-6">
                    <x-input-label for="file" value="{{ __('File Excel') }}" class="sr-only" />
                    <input id="file" name="file" type="file" class="block w-full text-sm text-slate-500
                    file:mr-4 file:py-2 file:px-4
                    file:rounded-full file:border-0
                    file:text-sm file:font-semibold
                    file:bg-sky-50 file:text-sky-700
                    hover:file:bg-sky-100 dark:file:bg-slate-700 dark:file:text-slate-300
                " accept=".xlsx, .xls, .csv" required />
                    <x-input-error :messages="$errors->get('file')" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end">
                    <x-secondary-button x-on:click="$dispatch('close')">
                        {{ __('Batal') }}
                    </x-secondary-button>

                    <x-primary-button class="ml-3">
                        {{ __('Import') }}
                    </x-primary-button>
                </div>
            </form>
        </x-modal>

        <!-- Modal Delete Event Tersembunyi -->
        <form id="delete-event-form" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>

        @push('scripts')
            <!-- FullCalendar JS -->
            <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
            <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/id.js'></script>
            <!-- SweetAlert2 -->
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var calendarEl = document.getElementById('calendar');

                    // Konversi koleksi kalender ke format event FullCalendar
                    var rawEvents = @json($calendars);
                    var calendarEvents = rawEvents.map(function (item) {
                        return {
                            id: item.id,
                            title: item.title,
                            start: item.start_date,
                            // FullCalendar butuh end date +1 hari jika allday agar merender kotak dengan benar
                            end: item.end_date ? new Date(new Date(item.end_date).getTime() + 86400000).toISOString().split('T')[0] : item.start_date,
                            classNames: item.is_holiday ? ['holiday-event'] : ['activity-event'],
                            extendedProps: {
                                description: item.description,
                                is_holiday: item.is_holiday
                            },
                            allDay: true
                        };
                    });

                    var calendar = new FullCalendar.Calendar(calendarEl, {
                        locale: 'id',
                        initialView: 'dayGridMonth',
                        headerToolbar: {
                            left: 'prev,next today',
                            center: 'title',
                            right: 'dayGridMonth,dayGridWeek,listWeek'
                        },
                        events: calendarEvents,
                        eventClick: function (info) {
                            var eventObj = info.event;

                            var descText = eventObj.extendedProps.description ? '<br><span class="text-sm text-gray-500">' + eventObj.extendedProps.description + '</span>' : '';
                            var typeText = eventObj.extendedProps.is_holiday ? '<span class="px-2 py-1 text-xs font-semibold rounded bg-red-100 text-red-800">Hari Libur</span>' : '<span class="px-2 py-1 text-xs font-semibold rounded bg-green-100 text-green-800">Kegiatan</span>';

                            Swal.fire({
                                title: eventObj.title,
                                html: '<div class="text-left mt-4 mb-2"><strong>Tipe:</strong> ' + typeText + '</div><div class="text-left mb-4"><strong>Deskripsi:</strong>' + descText + '</div><p class="text-red-600 mt-4 text-sm font-medium">Apakah Anda yakin ingin menghapus agenda ini secara permanen?</p>',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#ef4444',
                                cancelButtonColor: '#64748b',
                                confirmButtonText: 'Ya, Hapus!',
                                cancelButtonText: 'Batal',
                                customClass: {
                                    popup: 'dark:bg-slate-800 dark:text-slate-100',
                                    title: 'text-gray-800 dark:text-gray-100',
                                    htmlContainer: 'text-gray-600 dark:text-gray-300'
                                }
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    var deleteForm = document.getElementById('delete-event-form');
                                    // Set URL action ke rute delete
                                    deleteForm.action = '/admin/calendars/' + eventObj.id;
                                    deleteForm.submit();
                                }
                            });
                        },
                        // Responsive options
                        windowResize: function (view) {
                            if (window.innerWidth < 768) {
                                calendar.changeView('listWeek');
                            } else {
                                calendar.changeView('dayGridMonth');
                            }
                        }
                    });

                    calendar.render();

                    // Cek inisialisasi awal responsif
                    if (window.innerWidth < 768) {
                        calendar.changeView('listWeek');
                    }
                });
            </script>
        @endpush
</x-app-layout>