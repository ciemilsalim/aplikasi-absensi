@php
    // Definisikan data untuk breadcrumb halaman ini
    $breadcrumbs = [
        ['title' => 'Data', 'url' => '#'], // Item tanpa link
        ['title' => 'Siswa', 'url' => route('admin.students.index')],
        ['title' => 'Edit Siswa', 'url' => route('admin.students.edit', $student)]
    ];
@endphp
<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :breadcrumbs="$breadcrumbs" class="mb-4" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Edit Data Siswa') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <form action="{{ route('admin.students.update', $student) }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="p-6 space-y-6">

                        <div>
                            <x-input-label for="name" :value="__('Nama Siswa')" />
                            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name"
                                :value="old('name', $student->name)" required autofocus />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>

                        <div>
                            <x-input-label for="nis" :value="__('Nomor Induk Siswa (NIS)')" />
                            <x-text-input id="nis" class="block mt-1 w-full" type="text" name="nis" :value="old('nis', $student->nis)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('nis')" />
                        </div>

                        {{-- Dropdown Pilihan Kelas BARU --}}
                        <div>
                            <x-input-label for="school_class_id" :value="__('Kelas')" />
                            <select name="school_class_id" id="school_class_id"
                                class="block mt-1 w-full border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 focus:border-sky-500 dark:focus:border-sky-600 focus:ring-sky-500 dark:focus:ring-sky-600 rounded-md shadow-sm">
                                <option value="">-- Pilih Kelas --</option>
                                @foreach ($classes as $class)
                                    <option value="{{ $class->id }}" {{ old('school_class_id', $student->school_class_id) == $class->id ? 'selected' : '' }}>
                                        {{ $class->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('school_class_id')" />
                        </div>

                        {{-- Added Photo Input, Webcam Capture and Preview --}}
                        <div x-data="{ 
                            useWebcam: false, 
                            stream: null,
                            capturedPhoto: null,
                            
                            startCamera() {
                                this.useWebcam = true;
                                navigator.mediaDevices.getUserMedia({ video: true })
                                    .then(s => {
                                        this.stream = s;
                                        this.$refs.video.srcObject = s;
                                    })
                                    .catch(err => {
                                        alert('Gagal mengakses kamera: ' + err.message);
                                        this.useWebcam = false;
                                    });
                            },
                            
                            stopCamera() {
                                if (this.stream) {
                                    this.stream.getTracks().forEach(track => track.stop());
                                    this.stream = null;
                                }
                                this.useWebcam = false;
                            },
                            
                            capturePhoto() {
                                const canvas = document.createElement('canvas');
                                canvas.width = this.$refs.video.videoWidth;
                                canvas.height = this.$refs.video.videoHeight;
                                canvas.getContext('2d').drawImage(this.$refs.video, 0, 0);
                                this.capturedPhoto = canvas.toDataURL('image/png');
                                this.$refs.webcamInput.value = this.capturedPhoto;
                                this.stopCamera();
                            },
                            
                            retakePhoto() {
                                this.capturedPhoto = null;
                                this.$refs.webcamInput.value = '';
                                this.startCamera();
                            }
                        }">
                            <x-input-label for="photo" :value="__('Foto Siswa')" />

                            @if($student->photo)
                                <div class="mt-2 mb-4" x-show="!capturedPhoto && !useWebcam">
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Foto Saat Ini:</p>
                                    <img src="{{ asset('storage/' . $student->photo) }}" alt="Foto Siswa"
                                        class="h-32 w-32 object-cover rounded-lg border border-gray-300 dark:border-slate-600">
                                </div>
                            @endif

                            <!-- Opsi Upload File -->
                            <div x-show="!useWebcam && !capturedPhoto">
                                <input id="photo" name="photo" type="file"
                                    class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-slate-700 dark:border-slate-600 dark:placeholder-gray-400"
                                    accept="image/*">
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-300">Biarkan kosong jika tidak ingin
                                    mengubah foto. Format: JPG, PNG, GIF. Maks: 2MB.</p>

                                <div class="mt-3 flex items-center gap-2">
                                    <span class="text-sm text-gray-500">atau</span>
                                    <button type="button" @click="startCamera()"
                                        class="px-3 py-1.5 bg-sky-100 text-sky-700 dark:bg-sky-900/50 dark:text-sky-400 rounded-md text-sm hover:bg-sky-200 dark:hover:bg-sky-900 transition-colors font-medium">
                                        Ambil Foto dari Kamera
                                    </button>
                                </div>
                            </div>

                            <!-- UI Kamera -->
                            <div x-show="useWebcam" class="mt-4" style="display: none;">
                                <div class="relative bg-black rounded-lg overflow-hidden max-w-sm aspect-square mb-3">
                                    <video x-ref="video" autoplay playsinline
                                        class="absolute inset-0 w-full h-full object-cover"></video>
                                </div>
                                <div class="flex gap-2">
                                    <button type="button" @click="capturePhoto()"
                                        class="px-4 py-2 bg-green-600 text-white rounded-md text-sm hover:bg-green-700 font-medium">Ambil
                                        Gambar</button>
                                    <button type="button" @click="stopCamera()"
                                        class="px-4 py-2 bg-gray-500 text-white rounded-md text-sm hover:bg-gray-600 font-medium">Batal</button>
                                </div>
                            </div>

                            <!-- Hasil Capture -->
                            <div x-show="capturedPhoto" class="mt-4" style="display: none;">
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Hasil Capture (Akan Disimpan):
                                </p>
                                <div class="relative max-w-sm aspect-square mb-3">
                                    <img :src="capturedPhoto"
                                        class="absolute inset-0 w-full h-full object-cover rounded-lg border border-gray-300 dark:border-slate-600">
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" @click="retakePhoto()"
                                        class="px-4 py-2 bg-amber-500 text-white rounded-md text-sm hover:bg-amber-600 font-medium">Foto
                                        Ulang</button>
                                    <button type="button" @click="capturedPhoto = null; $refs.webcamInput.value = ''"
                                        class="px-4 py-2 bg-gray-500 text-white rounded-md text-sm hover:bg-gray-600 font-medium">Hapus
                                        & Kembali ke Upload</button>
                                </div>
                            </div>

                            <input type="hidden" name="webcam_photo" x-ref="webcamInput">
                            <x-input-error :messages="$errors->get('photo')" class="mt-2" />
                        </div>

                    </div>
                    <div class="bg-gray-50 dark:bg-slate-800/50 px-6 py-4 flex items-center justify-end gap-4">
                        <a href="{{ route('admin.students.index') }}" class="text-sm font-medium">Batal</a>
                        <x-primary-button>Perbarui Siswa</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>