<?php
// File: app/Models/LeaveRequest.php (Baru)
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    use HasFactory;
    protected $fillable = [
        'student_id', 'parent_id', 'start_date', 'end_date', 'type', 'submission_source', 'reason', 
        'attachment', 'status', 'approved_by', 'created_by', 'rejection_reason'
    ];
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];
    public function student() { return $this->belongsTo(Student::class); }
    public function parent() { return $this->belongsTo(ParentModel::class); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}