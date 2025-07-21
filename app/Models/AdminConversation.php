<?php

// File: app/Models/AdminConversation.php (Baru)
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AdminConversation extends Model {
    protected $fillable = ['parent_id', 'admin_id'];
    public function messages() { return $this->hasMany(AdminMessage::class); }
    public function parent() { return $this->belongsTo(ParentModel::class); }
    public function admin() { return $this->belongsTo(User::class, 'admin_id'); }
}
