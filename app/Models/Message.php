<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Message extends Model {
    protected $fillable = ['conversation_id', 'user_id', 'body', 'read_at'];
    
    // Secara otomatis mengubah kolom 'read_at' menjadi objek Carbon untuk kemudahan manipulasi tanggal
    protected $casts = ['read_at' => 'datetime'];

    // Relasi: Satu pesan dimiliki oleh satu percakapan
    public function conversation() { return $this->belongsTo(Conversation::class); }
    // Relasi: Satu pesan dikirim oleh satu user
    public function user() { return $this->belongsTo(User::class); }
}