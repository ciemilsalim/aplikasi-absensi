<?php

// File: app/Models/AdminMessage.php (Baru)
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AdminMessage extends Model {
    protected $fillable = ['admin_conversation_id', 'user_id', 'body', 'read_at'];
    protected $casts = ['read_at' => 'datetime'];
    public function conversation() { return $this->belongsTo(AdminConversation::class, 'admin_conversation_id'); }
    public function user() { return $this->belongsTo(User::class); }
}
