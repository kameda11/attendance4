<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'break_id',
        'attendance_id',
        'target_date',
        'request_type',
        'status',
        'start_time',
        'end_time',
        'notes',
    ];

    protected $casts = [
        'target_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * 申請者とのリレーション
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 対象の休憩とのリレーション
     */
    public function break()
    {
        return $this->belongsTo(Breaktime::class, 'break_id');
    }

    /**
     * 勤怠記録とのリレーション
     */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * 申請が承認待ちかチェック
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * 申請が承認済みかチェック
     */
    public function isApproved()
    {
        return $this->status === 'approved';
    }

    /**
     * 申請が却下済みかチェック
     */
    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    /**
     * 新規作成申請かチェック
     */
    public function isCreateRequest()
    {
        return $this->request_type === 'create';
    }

    /**
     * 修正申請かチェック
     */
    public function isUpdateRequest()
    {
        return $this->request_type === 'update';
    }
}
