<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Breaktime extends Model
{
    use HasFactory;

    protected $table = 'breaks';

    protected $fillable = [
        'attendance_id',
        'start_time',
        'end_time',
        'notes',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * 勤怠記録とのリレーション
     */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * 休憩時間を分で取得
     */
    public function getDurationInMinutes()
    {
        if (!$this->end_time) {
            return null;
        }

        return $this->start_time->diffInMinutes($this->end_time);
    }

    /**
     * 休憩が終了しているかチェック
     */
    public function isCompleted()
    {
        return !is_null($this->end_time);
    }

    /**
     * 現在進行中の休憩かチェック
     */
    public function isActive()
    {
        return is_null($this->end_time);
    }
}
