<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'attendance_id',
        'target_date',
        'request_type',
        'status',
        'clock_in_time',
        'clock_out_time',
        'notes',
    ];

    protected $casts = [
        'target_date' => 'date',
        'clock_in_time' => 'datetime',
        'clock_out_time' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    // 申請ステータスの定数
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';

    // 申請タイプの定数
    const TYPE_CREATE = 'create';
    const TYPE_UPDATE = 'update';

    // ステータスラベル
    public function getStatusLabelAttribute()
    {
        return [
            self::STATUS_PENDING => '申請中',
            self::STATUS_APPROVED => '承認済み',
        ][$this->status] ?? $this->status;
    }

    // 申請タイプラベル
    public function getRequestTypeLabelAttribute()
    {
        return [
            self::TYPE_CREATE => '新規作成',
            self::TYPE_UPDATE => '修正申請',
        ][$this->request_type] ?? $this->request_type;
    }
}
