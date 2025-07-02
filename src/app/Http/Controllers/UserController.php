<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\Breaktime;
use App\Models\BreakRequest;
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
            ->with('breakTimes')
            ->whereDate('created_at', today())
            ->first();

        $recentAttendances = $user->attendances()
            ->with('breakTimes')
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

        // 新しい休憩記録を作成
        $break = $attendance->breakTimes()->create([
            'start_time' => now(),
        ]);

        $attendance->update([
            'status' => 'break',
        ]);

        return response()->json([
            'success' => true,
            'attendance' => $attendance,
            'break' => $break
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
            ->with('breakTimes')
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

        // 最新の未終了の休憩を取得
        $activeBreak = $attendance->breakTimes()
            ->whereNull('end_time')
            ->latest()
            ->first();

        if ($activeBreak) {
            $activeBreak->update([
                'end_time' => now(),
            ]);
        }

        $attendance->update([
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
            ->with('breakTimes')
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
            ->with('breakTimes')
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
                    $workTime = $this->calculateWorkTime($attendance->clock_in_time, $attendance->clock_out_time, $attendance->breakTimes);
                }

                if ($attendance->breakTimes->isNotEmpty()) {
                    $breakTime = $this->calculateBreakTime($attendance->breakTimes);
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
    private function calculateWorkTime($clockIn, $clockOut, $breakTimes = [])
    {
        $totalMinutes = $clockIn->diffInMinutes($clockOut);

        // 休憩時間を差し引く
        foreach ($breakTimes as $break) {
            if ($break->start_time && $break->end_time) {
                $breakMinutes = $break->start_time->diffInMinutes($break->end_time);
                $totalMinutes -= $breakMinutes;
            }
        }

        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        return sprintf('%d:%02d', $hours, $minutes);
    }

    /**
     * 休憩時間を計算
     */
    private function calculateBreakTime($breakTimes)
    {
        $totalMinutes = 0;
        foreach ($breakTimes as $break) {
            if ($break->start_time && $break->end_time) {
                $totalMinutes += $break->start_time->diffInMinutes($break->end_time);
            }
        }

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
                foreach ($attendance->breakTimes as $break) {
                    if ($break->start_time && $break->end_time) {
                        $breakMinutes = $break->start_time->diffInMinutes($break->end_time);
                        $workMinutes -= $breakMinutes;
                        $totalBreakMinutes += $breakMinutes;
                    }
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
            $attendance = $user->attendances()->with('breakTimes')->findOrFail($id);
            $date = $attendance->created_at->format('Y-m-d');
        }

        return view('attendance.detail', compact('attendance', 'date'));
    }

    /**
     * 勤怠修正申請を作成
     */
    public function attendanceUpdate(AttendanceUpdateRequest $request, $id)
    {
        try {
            $user = Auth::user();
            $attendance = $user->attendances()->findOrFail($id);

            // 既に保留中の申請があるかチェック
            $existingRequest = $user->attendanceRequests()
                ->where('attendance_id', $id)
                ->where('status', 'pending')
                ->first();

            if ($existingRequest) {
                return back()->withErrors(['general' => '既に保留中の申請があります。']);
            }

            $requestData = [
                'user_id' => $user->id,
                'attendance_id' => $id,
                'target_date' => $attendance->created_at->format('Y-m-d'),
                'request_type' => 'update',
                'status' => 'pending',
                'notes' => $request->notes,
            ];

            // 時間データの処理
            if ($request->clock_in_time) {
                $requestData['clock_in_time'] = $request->clock_in_time . ':00';
            }
            if ($request->clock_out_time) {
                $requestData['clock_out_time'] = $request->clock_out_time . ':00';
            }

            // 勤怠申請を作成
            $attendanceRequest = AttendanceRequest::create($requestData);

            // 休憩時間の申請処理
            $this->processBreakRequests($user, $attendance, $request);

            return redirect()->route('user.attendance.list');
        } catch (\Exception $e) {
            return back()->withErrors(['general' => 'エラーが発生しました: ' . $e->getMessage()]);
        }
    }

    /**
     * 休憩申請を処理
     */
    private function processBreakRequests($user, $attendance, $request)
    {
        // 休憩1の申請処理
        if ($request->break1_start_time || $request->break1_end_time) {
            $this->createBreakRequest($user, $attendance, $request, 1);
        }

        // 休憩2の申請処理
        if ($request->break2_start_time || $request->break2_end_time) {
            $this->createBreakRequest($user, $attendance, $request, 2);
        }
    }

    /**
     * 個別の休憩申請を作成
     */
    private function createBreakRequest($user, $attendance, $request, $breakNumber)
    {
        $startTimeField = "break{$breakNumber}_start_time";
        $endTimeField = "break{$breakNumber}_end_time";

        // 既存の休憩を取得
        $existingBreak = $attendance->breakTimes()->skip($breakNumber - 1)->first();

        $requestData = [
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'target_date' => $attendance->created_at->format('Y-m-d'),
            'status' => 'pending',
        ];

        if ($existingBreak) {
            // 既存の休憩を修正する場合
            $requestData['break_id'] = $existingBreak->id;
            $requestData['request_type'] = 'update';
        } else {
            // 新しい休憩を作成する場合
            $requestData['break_id'] = null;
            $requestData['request_type'] = 'create';
        }

        // 時間データの処理
        if ($request->$startTimeField) {
            $requestData['start_time'] = $request->$startTimeField . ':00';
        }
        if ($request->$endTimeField) {
            $requestData['end_time'] = $request->$endTimeField . ':00';
        }

        // 既に保留中の申請があるかチェック
        $existingRequest = $user->breakRequests()
            ->where('attendance_id', $attendance->id)
            ->where('status', 'pending')
            ->first();

        if (!$existingRequest) {
            BreakRequest::create($requestData);
        }
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
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            return back()->withErrors(['date' => '既に保留中の申請があります。']);
        }

        $requestData = [
            'user_id' => $user->id,
            'attendance_id' => null,
            'target_date' => $request->date,
            'request_type' => 'create',
            'status' => 'pending',
            'notes' => $request->notes,
        ];

        // 時間データの処理
        if ($request->clock_in_time) {
            $requestData['clock_in_time'] = $request->clock_in_time . ':00';
        }
        if ($request->clock_out_time) {
            $requestData['clock_out_time'] = $request->clock_out_time . ':00';
        }

        // 勤怠申請を作成
        $attendanceRequest = AttendanceRequest::create($requestData);

        // 休憩時間の申請処理（新規作成の場合）
        $this->processBreakRequestsForNew($user, $request);

        return redirect()->route('user.attendance.list');
    }

    /**
     * 新規作成時の休憩申請を処理
     */
    private function processBreakRequestsForNew($user, $request)
    {
        // 休憩1の申請処理
        if ($request->break1_start_time || $request->break1_end_time) {
            $this->createBreakRequestForNew($user, $request, 1);
        }

        // 休憩2の申請処理
        if ($request->break2_start_time || $request->break2_end_time) {
            $this->createBreakRequestForNew($user, $request, 2);
        }
    }

    /**
     * 新規作成時の個別の休憩申請を作成
     */
    private function createBreakRequestForNew($user, $request, $breakNumber)
    {
        $startTimeField = "break{$breakNumber}_start_time";
        $endTimeField = "break{$breakNumber}_end_time";

        $requestData = [
            'user_id' => $user->id,
            'break_id' => null,
            'attendance_id' => null, // 新規作成時はattendance_idはnull
            'target_date' => $request->date,
            'request_type' => 'create',
            'status' => 'pending',
        ];

        // 時間データの処理
        if ($request->$startTimeField) {
            $requestData['start_time'] = $request->$startTimeField . ':00';
        }
        if ($request->$endTimeField) {
            $requestData['end_time'] = $request->$endTimeField . ':00';
        }

        BreakRequest::create($requestData);
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

    /**
     * 申請一覧を表示（stamp_correction_request用）
     */
    public function stampCorrectionRequests(Request $request)
    {
        $user = Auth::user();
        $status = $request->get('status', 'pending');

        // 承認待ちと承認済みの件数を取得（勤怠申請 + 休憩申請）
        $pendingCount = $user->attendanceRequests()->where('status', 'pending')->count() +
            $user->breakRequests()->where('status', 'pending')->count();
        $approvedCount = $user->attendanceRequests()->where('status', 'approved')->count() +
            $user->breakRequests()->where('status', 'approved')->count();

        // 勤怠申請を取得
        $attendanceRequests = $user->attendanceRequests()
            ->with('user')
            ->where('status', $status)
            ->get()
            ->map(function ($request) {
                $request->request_type = 'attendance';
                // その日付の勤怠IDを取得
                $attendance = Attendance::where('user_id', $request->user_id)
                    ->whereDate('created_at', $request->target_date)
                    ->first();
                $request->attendance_id = $attendance ? $attendance->id : 0;
                return $request;
            });

        // 休憩申請を取得
        $breakRequests = $user->breakRequests()
            ->with('user')
            ->where('status', $status)
            ->get()
            ->map(function ($request) {
                $request->request_type = 'break';
                // その日付の勤怠IDを取得
                $attendance = Attendance::where('user_id', $request->user_id)
                    ->whereDate('created_at', $request->target_date)
                    ->first();
                $request->attendance_id = $attendance ? $attendance->id : 0;
                return $request;
            });

        // 両方の申請を結合して日時順にソート
        $allRequests = $attendanceRequests->concat($breakRequests)
            ->sortByDesc('created_at');

        // ページネーション用に配列を分割
        $perPage = 20;
        $currentPage = $request->get('page', 1);
        $offset = ($currentPage - 1) * $perPage;
        $requests = $allRequests->slice($offset, $perPage);

        // 手動でページネーション情報を作成
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $requests,
            $allRequests->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'pageName' => 'page',
            ]
        );

        return view('stamp_correction_request.list', compact('paginator', 'status', 'pendingCount', 'approvedCount'));
    }

    /**
     * 休憩申請一覧を表示
     */
    public function breakRequests(Request $request)
    {
        $user = Auth::user();
        $requests = $user->breakRequests()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('attendance.break-requests', compact('requests'));
    }

    /**
     * 休憩修正申請を作成
     */
    public function breakUpdate(Request $request, $id)
    {
        $user = Auth::user();
        $break = Breaktime::with('attendance')->findOrFail($id);

        // 自分の勤怠記録の休憩かチェック
        if ($break->attendance->user_id !== $user->id) {
            abort(403, 'アクセス権限がありません。');
        }

        // 既に保留中の申請があるかチェック
        $existingRequest = $user->breakRequests()
            ->where('break_id', $id)
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            return back()->withErrors(['general' => '既に保留中の申請があります。']);
        }

        $request->validate([
            'start_time' => 'required|regex:/^[0-9]{1,2}:[0-9]{2}$/',
            'end_time' => 'nullable|regex:/^[0-9]{1,2}:[0-9]{2}$/',
            'notes' => 'nullable|string|max:500',
        ]);

        $requestData = [
            'user_id' => $user->id,
            'break_id' => $id,
            'attendance_id' => $break->attendance_id,
            'target_date' => $break->attendance->created_at->format('Y-m-d'),
            'request_type' => 'update',
            'status' => 'pending',
            'start_time' => $request->start_time . ':00',
            'end_time' => $request->end_time ? $request->end_time . ':00' : null,
            'notes' => $request->notes,
        ];

        BreakRequest::create($requestData);

        return redirect()->route('user.attendance.list');
    }

    /**
     * 休憩新規作成申請を作成
     */
    public function breakStore(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'attendance_id' => 'required|exists:attendances,id',
            'start_time' => 'required|regex:/^[0-9]{1,2}:[0-9]{2}$/',
            'end_time' => 'nullable|regex:/^[0-9]{1,2}:[0-9]{2}$/',
            'notes' => 'nullable|string|max:500',
        ]);

        $attendance = Attendance::findOrFail($request->attendance_id);

        // 自分の勤怠記録かチェック
        if ($attendance->user_id !== $user->id) {
            abort(403, 'アクセス権限がありません。');
        }

        // 既に保留中の申請があるかチェック
        $existingRequest = $user->breakRequests()
            ->where('attendance_id', $request->attendance_id)
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            return back()->withErrors(['general' => '既に保留中の申請があります。']);
        }

        $requestData = [
            'user_id' => $user->id,
            'break_id' => null,
            'attendance_id' => $request->attendance_id,
            'target_date' => $attendance->created_at->format('Y-m-d'),
            'request_type' => 'create',
            'status' => 'pending',
            'start_time' => $request->start_time . ':00',
            'end_time' => $request->end_time ? $request->end_time . ':00' : null,
            'notes' => $request->notes,
        ];

        BreakRequest::create($requestData);

        return redirect()->route('user.attendance.list');
    }
}
