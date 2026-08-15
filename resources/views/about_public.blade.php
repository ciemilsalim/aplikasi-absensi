@extends('layouts.public')

@section('title', 'Tentang Aplikasi - ' . config('app.name', 'Presensi Siswa'))

@section('content')
<div class="py-8 sm:py-16">
    @include('partials._about-content')
</div>
@endsection
