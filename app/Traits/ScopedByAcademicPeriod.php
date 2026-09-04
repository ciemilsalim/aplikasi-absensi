<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait ScopedByAcademicPeriod
{
    protected static function bootScopedByAcademicPeriod()
    {
        static::addGlobalScope('academic_period', function (Builder $builder) {
            // Hanya aplikasikan scope jika ada sesi aktif dan tidak sedang berjalan di CLI
            if (session()->has('active_semester_id')) {
                $table = $builder->getModel()->getTable();
                $activeSemesterId = session('active_semester_id');
                $activeYearId = session('active_academic_year_id');

                $builder->where(function ($q) use ($table, $activeSemesterId, $activeYearId) {
                    $q->where($table . '.semester_id', $activeSemesterId)
                      ->orWhereNull($table . '.semester_id');
                    if ($activeYearId) {
                        $q->orWhere($table . '.academic_year_id', $activeYearId);
                    }
                });
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
