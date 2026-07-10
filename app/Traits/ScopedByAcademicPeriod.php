<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait ScopedByAcademicPeriod
{
    protected static function bootScopedByAcademicPeriod()
    {
        static::addGlobalScope('academic_period', function (Builder $builder) {
            // Hanya aplikasikan scope jika ada sesi aktif dan tidak sedang berjalan di CLI (opsional, tapi session()->has aman)
            if (session()->has('active_semester_id')) {
                // Gunakan nama tabel agar tidak ambigu pada query join
                $table = $builder->getModel()->getTable();
                $builder->where($table . '.semester_id', session('active_semester_id'));
            }
        });

        // Otomatis isi semester_id dan academic_year_id saat membuat data baru
        static::creating(function ($model) {
            if (session()->has('active_semester_id') && empty($model->semester_id)) {
                $model->semester_id = session('active_semester_id');
                $model->academic_year_id = session('active_academic_year_id');
            }
        });
    }
}
