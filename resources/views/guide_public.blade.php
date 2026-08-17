@extends('layouts.public')

@section('title', 'Panduan Penggunaan - ' . config('app.name', 'Presensi Siswa'))

@section('content')
<div class="py-8 sm:py-16">
    @include('partials._guide-content', ['defaultTab' => $defaultTab ?? 'all'])
</div>
@endsection
