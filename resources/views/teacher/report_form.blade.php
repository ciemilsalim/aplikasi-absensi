<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Rekap Kehadiran Mata Pelajaran') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    
                    <form action="{{ route('teacher.subject.attendance.print') }}" method="GET" target="_blank">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            
                            <!-- Filter Tanggal Mulai -->
                            <div>
                                <label for="start_date" class="block font-medium text-sm text-gray-700">{{ __('Tanggal Mulai') }}</label>
                                <input id="start_date" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" type="date" name="start_date" value="{{ old('start_date', now()->startOfMonth()->format('Y-m-d')) }}" required />
                            </div>

                            <!-- Filter Tanggal Selesai -->
                            <div>
                                <label for="end_date" class="block font-medium text-sm text-gray-700">{{ __('Tanggal Selesai') }}</label>
                                <input id="end_date" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" type="date" name="end_date" value="{{ old('end_date', now()->format('Y-m-d')) }}" required />
                            </div>

                            <!-- Filter Kelas -->
                            <div>
                                <label for="school_class_id" class="block font-medium text-sm text-gray-700">{{ __('Pilih Kelas') }}</label>
                                <select id="school_class_id" name="school_class_id" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                                    <option value="">-- Semua Kelas --</option>
                                    @foreach($classes as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Filter Mata Pelajaran -->
                            <div>
                                <label for="subject_id" class="block font-medium text-sm text-gray-700">{{ __('Pilih Mata Pelajaran') }}</label>
                                <select id="subject_id" name="subject_id" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                                    <option value="">-- Semua Mapel --</option>
                                    @foreach($subjects as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-6">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                                {{ __('Tampilkan & Cetak Rekap') }}
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
