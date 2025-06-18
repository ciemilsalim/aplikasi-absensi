@extends('layouts.public')

@section('title', 'Daftar Siswa')

@section('content')
<div class="bg-white p-8 rounded-2xl shadow-md border border-slate-200">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-slate-800">Daftar Siswa</h1>
        <button onclick="window.print()" class="px-4 py-2 text-sm font-medium text-white bg-sky-600 rounded-lg hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500">
            Cetak Kartu
        </button>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        @forelse ($students as $student)
            <div class="card-container p-4 border border-slate-200 rounded-lg text-center flex flex-col items-center justify-center break-inside-avoid">
                <div class="qr-code mb-3">
                    {!! QrCode::size(120)->generate($student->unique_id) !!}
                </div>
                <h3 class="font-semibold text-slate-800">{{ $student->name }}</h3>
                <p class="text-sm text-slate-500">NIS: {{ $student->nis }}</p>
            </div>
        @empty
            <p class="text-slate-500 col-span-full">Belum ada data siswa.</p>
        @endforelse
    </div>
</div>

<style>
    @media print {
        body * {
            visibility: hidden;
        }
        .card-container, .card-container * {
            visibility: visible;
        }
        .grid {
            display: grid !important;
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 1rem !important;
        }
        main, .bg-white {
            box-shadow: none !important;
            border: none !important;
            padding: 0 !important;
        }
        header, button {
            display: none !important;
        }
    }
    .break-inside-avoid {
        break-inside: avoid;
    }
</style>
@endsection
