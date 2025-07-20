<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Conversation extends Model {
    // Properti yang diizinkan untuk diisi secara massal
    protected $fillable = ['parent_id', 'teacher_id', 'student_id'];

    // Relasi: Satu percakapan memiliki banyak pesan
    public function messages() { return $this->hasMany(Message::class); }
    // Relasi: Satu percakapan dimiliki oleh satu orang tua
    public function parent() { return $this->belongsTo(ParentModel::class); }
    // Relasi: Satu percakapan dimiliki oleh satu guru
    public function teacher() { return $this->belongsTo(Teacher::class); }
    // Relasi: Satu percakapan membahas satu siswa
    public function student() { return $this->belongsTo(Student::class); }
}
