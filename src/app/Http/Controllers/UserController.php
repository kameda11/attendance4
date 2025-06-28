<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Http\Requests\AttendanceRequest as AttendanceUpdateRequest;
use App\Http\Requests\AttendanceStoreRequest;

class UserController extends Controller
{
    /**
     * 勤務管理ページを表示
     */
    public function attendance()
    {
        $user = Auth::user();
        $todayAttendance = $user->attendances()
            ->whereDate('created_at', today())
            ->first();

        $recentAttendances = $user->attendances()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('attendance', compact('user', 'todayAttendance', 'recentAttendances'));
    }

    /**
     * 出勤記録を作成
     */
    public function clockIn(Request $request)
    {
        $user = Auth::user();

        // 今日の出勤記録を取得
        $existingAttendance = $user->attendances()
            ->whereDate('created_at', today())
            ->first();

        // 今日の記録が既に存在する場合はエラー
        if ($existingAttendance) {
            return response()->json([
                'success' => false
            ]);
        }

        // 出勤記録を作成
        $attendance = $user->attendances()->create([
            'clock_in_time' => now(),
            'status' => 'working',
        ]);

        return response()->json([
            'success' => true,
            'attendance' => $attendance
        ]);
    }

    /**
     * 退勤記録を更新
     */
    public function clockOut(Request $request)
    {
        $user = Auth::user();

        // 今日の最新の出勤記録を取得
        $attendance = $user->attendances()
            ->whereDate('created_at', today())
            ->where('status', '!=', 'completed')
            ->latest()
            ->first();

        if (!$attendance) {
            return response()->json([
                'success' => false
            ]);
        }

        $attendance->update([
            'clock_out_time' => now(),
            'status' => 'completed',
        ]);

        return response()->json([
            'success' => true,
            'attendance' => $attendance
        ]);
    }

    /**
     * 休憩開始記録
     */
    public function breakStart(Request $request)
    {
        $user = Auth::user();

        // 今日の最新の勤務記録を取得（退勤済みでないもの）
        $attendance = $user->attendances()
            ->whereDate('created_at', today())
            ->where('status', '!=', 'completed')
            ->latest()
            ->first();

        if (!$attendance) {
            return response()->json([
                'success' => false
            ]);
        }

        if ($attendance->status !== 'working') {
            return response()->json([
                'success' => false
            ]);
        }

        $attendance->update([
            'break_start_time' => now(),
            'status' => 'break',
        ]);

        return response()->json([
            'success' => true,
            'attendance' => $attendance
        ]);
    }

    /**
     * 休憩終了記録
     */
    public function breakEnd(Request $request)
    {
        $user = Auth::user();

        // 今日の最新の勤務記録を取得（退勤済みでないもの）
        $attendance = $user->attendances()
            ->whereDate('created_at', today())
            ->where('status', '!=', 'completed')
            ->latest()
            ->first();

        if (!$attendance) {
            return response()->json([
                'success' => false
            ]);
        }

        if ($attendance->status !== 'break') {
            return response()->json([
                'success' => false
            ]);
        }

        $attendance->update([
            'break_end_time' => now(),
            'status' => 'working',
        ]);

        return response()->json([
            'success' => true,
            'attendance' => $attendance
        ]);
    }

    /**
     * 勤務履歴を表示
     */
    public function attendanceHistory(Request $request)
    {
        $user = Auth::user();
        $attendances = $user->attendances()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('user.attendance-history', compact('attendances'));
    }

    /**
     * 勤怠一覧を表示
     */
    public function attendanceList(Request $request)
    {
        $user = Auth::user();

        // 年月の取得（デフォルトは現在の年月）
        $year = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);

        $currentMonth = \Carbon\Carbon::create($year, $month, 1);
        $prevMonth = $currentMonth->copy()->subMonth();
        $nextMonth = $currentMonth->copy()->addMonth();

        // 月の開始日と終了日
        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();

        // その月の勤怠データを取得
        $attendances = $user->attendances()
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->get()
            ->keyBy(function ($attendance) {
                return $attendance->created_at->format('Y-m-d');
            });

        // カレンダー配列を作成
        $calendar = [];
        $currentDate = $startOfMonth->copy();

        while ($currentDate <= $endOfMonth) {
            $dateKey = $currentDate->format('Y-m-d');
            $attendance = $attendances->get($dateKey);

            // 勤務時間と休憩時間の計算
            $workTime = '';
            $breakTime = '';

            if ($attendance) {
                if ($attendance->clock_in_time && $attendance->clock_out_time) {
                    $workTime = $this->calculateWorkTime($attendance->clock_in_time, $attendance->clock_out_time, $attendance->break_start_time, $attendance->break_end_time);
                }

                if ($attendance->break_start_time && $attendance->break_end_time) {
                    $breakTime = $this->calculateBreakTime($attendance->break_start_time, $attendance->break_end_time);
                }
            }

            $calendar[] = [
                'day' => $currentDate->format('j'),
                'weekday' => $this->getJapaneseWeekday($currentDate->dayOfWeek),
                'date' => $currentDate->format('Y-m-d'),
                'attendance' => $attendance,
                'workTime' => $workTime,
                'breakTime' => $breakTime,
                'isToday' => $currentDate->isToday(),
                'isWeekend' => $currentDate->isWeekend(),
            ];

            $currentDate->addDay();
        }

        // サマリー計算
        $summary = $this->calculateSummary($attendances);

        return view('attendance.list', compact('calendar', 'currentMonth', 'prevMonth', 'nextMonth', 'summary'));
    }

    /**
     * 勤務時間を計算
     */
    private function calculateWorkTime($clockIn, $clockOut, $breakStart = null, $breakEnd = null)
    {
        $totalMinutes = $clockIn->diffInMinutes($clockOut);

        // 休憩時間を差し引く
        if ($breakStart && $breakEnd) {
            $breakMinutes = $breakStart->diffInMinutes($breakEnd);
            $totalMinutes -= $breakMinutes;
        }

        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        return sprintf('%d:%02d', $hours, $minutes);
    }

    /**
     * 休憩時間を計算
     */
    private function calculateBreakTime($breakStart, $breakEnd)
    {
        $totalMinutes = $breakStart->diffInMinutes($breakEnd);
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        return sprintf('%d:%02d', $hours, $minutes);
    }

    /**
     * 日本語の曜日を取得
     */
    private function getJapaneseWeekday($dayOfWeek)
    {
        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        return $weekdays[$dayOfWeek];
    }

    /**
     * 月間サマリーを計算
     */
    private function calculateSummary($attendances)
    {
        $workDays = 0;
        $totalWorkMinutes = 0;
        $totalBreakMinutes = 0;

        foreach ($attendances as $attendance) {
            if ($attendance->clock_in_time && $attendance->clock_out_time) {
                $workDays++;

                $workMinutes = $attendance->clock_in_time->diffInMinutes($attendance->clock_out_time);

                // 休憩時間を差し引く
                if ($attendance->break_start_time && $attendance->break_end_time) {
                    $breakMinutes = $attendance->break_start_time->diffInMinutes($attendance->break_end_time);
                    $workMinutes -= $breakMinutes;
                    $totalBreakMinutes += $breakMinutes;
                }

                $totalWorkMinutes += $workMinutes;
            }
        }

        return [
            'workDays' => $workDays,
            'totalWorkHours' => round($totalWorkMinutes / 60, 1),
            'totalBreakHours' => round($totalBreakMinutes / 60, 1),
        ];
    }

    public function attendanceDetail($id)
    {
        $user = Auth::user();

        if ($id == 0) {
            // 勤怠記録がない場合
            $attendance = null;
            $date = request()->get('date', now()->format('Y-m-d'));
        } else {
            $attendance = $user->attendances()->findOrFail($id);
            $date = $attendance->created_at->format('Y-m-d');
        }

        return view('attendance.detail', compact('attendance', 'date'));
    }

    /**
     * 勤怠修正申請を作成
     */
    public function attendanceUpdate(AttendanceUpdateRequest $request, $id)
    {
        $user = Auth::user();
        $attendance = $user->attendances()->findOrFail($id);

        // 既に保留中の申請があるかチェック
        $existingRequest = $user->attendanceRequests()
            ->where('attendance_id', $id)
            ->where('status', AttendanceRequest::STATUS_PENDING)
            ->first();

        if ($existingRequest) {
            return back()->withErrors(['general' => '既に保留中の申請があります。']);
        }

        $requestData = [
            'user_id' => $user->id,
            'attendance_id' => $id,
            'target_date' => $attendance->created_at->format('Y-m-d'),
            'request_type' => AttendanceRequest::TYPE_UPDATE,
            'status' => AttendanceRequest::STATUS_PENDING,
            'notes' => $request->notes,
        ];

        // 時間データの処理
        if ($request->clock_in_time) {
            $requestData['clock_in_time'] = $request->clock_in_time . ':00';
        }
        if ($request->clock_out_time) {
            $requestData['clock_out_time'] = $request->clock_out_time . ':00';
        }
        if ($request->break_start_time) {
            $requestData['break_start_time'] = $request->break_start_time . ':00';
        }
        if ($request->break_end_time) {
            $requestData['break_end_time'] = $request->break_end_time . ':00';
        }
        if ($request->break2_start_time) {
            $requestData['break2_start_time'] = $request->break2_start_time . ':00';
        }
        if ($request->break2_end_time) {
            $requestData['break2_end_time'] = $request->break2_end_time . ':00';
        }

        AttendanceRequest::create($requestData);

        return redirect()->route('user.attendance.list');
    }

    /**
     * 勤怠新規作成申請を作成
     */
    public function attendanceStore(AttendanceStoreRequest $request)
    {
        $user = Auth::user();

        // 指定された日付で既に勤怠記録が存在するかチェック
        $existingAttendance = $user->attendances()
            ->whereDate('created_at', $request->date)
            ->first();

        if ($existingAttendance) {
            return back()->withErrors(['date' => '指定された日付には既に勤怠記録が存在します。']);
        }

        // 既に保留中の申請があるかチェック
        $existingRequest = $user->attendanceRequests()
            ->where('target_date', $request->date)
            ->where('status', AttendanceRequest::STATUS_PENDING)
            ->first();

        if ($existingRequest) {
            return back()->withErrors(['date' => '既に保留中の申請があります。']);
        }

        $requestData = [
            'user_id' => $user->id,
            'attendance_id' => null,
            'target_date' => $request->date,
            'request_type' => AttendanceRequest::TYPE_CREATE,
            'status' => AttendanceRequest::STATUS_PENDING,
            'notes' => $request->notes,
        ];

        // 時間データの処理
        if ($request->clock_in_time) {
            $requestData['clock_in_time'] = $request->clock_in_time . ':00';
        }
        if ($request->clock_out_time) {
            $requestData['clock_out_time'] = $request->clock_out_time . ':00';
        }
        if ($request->break_start_time) {
            $requestData['break_start_time'] = $request->break_start_time . ':00';
        }
        if ($request->break_end_time) {
            $requestData['break_end_time'] = $request->break_end_time . ':00';
        }
        if ($request->break2_start_time) {
            $requestData['break2_start_time'] = $request->break2_start_time . ':00';
        }
        if ($request->break2_end_time) {
            $requestData['break2_end_time'] = $request->break2_end_time . ':00';
        }

        AttendanceRequest::create($requestData);

        return redirect()->route('user.attendance.list');
    }

    /**
     * 申請一覧を表示
     */
    public function attendanceRequests(Request $request)
    {
        $user = Auth::user();
        $requests = $user->attendanceRequests()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('attendance.requests', compact('requests'));
    }
}
