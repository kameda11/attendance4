<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Admin;
use App\Models\Attendance;
use App\Models\User;
use App\Models\AttendanceRequest;
use App\Models\Breaktime;
use App\Models\BreakRequest;
use Carbon\Carbon;

class AdminController extends Controller
{
    /**
     * 管理者ログインフォームを表示
     */
    public function showLoginForm()
    {
        return view('admin.login');
    }

    /**
     * 管理者ログイン処理
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // 固定の管理者認証情報
        $adminEmail = 'admin@email';
        $adminPassword = 'admin123';

        // 固定の認証情報と比較
        if ($credentials['email'] === $adminEmail && $credentials['password'] === $adminPassword) {
            // 管理者セッションを作成
            $request->session()->put('admin_logged_in', true);
            $request->session()->put('admin_email', $adminEmail);
            $request->session()->regenerate();

            return redirect()->route('admin.attendances');
        }

        return back()->withErrors([
            'email' => 'メールアドレスまたはパスワードが正しくありません。',
        ])->onlyInput('email');
    }

    /**
     * 管理者ログアウト処理
     */
    public function logout(Request $request)
    {
        $request->session()->forget('admin_logged_in');
        $request->session()->forget('admin_email');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login.form');
    }

    /**
     * スタッフ一覧表示
     */
    public function users()
    {
        $users = User::orderBy('name', 'asc')->get();

        return view('admin.users', compact('users'));
    }

    /**
     * 申請一覧表示
     */
    public function requests(Request $request)
    {
        $status = $request->get('status', 'pending');

        // 承認待ちと承認済みの件数を取得（勤怠申請 + 休憩申請）
        $pendingCount = AttendanceRequest::where('status', 'pending')->count() +
            BreakRequest::where('status', 'pending')->count();
        $approvedCount = AttendanceRequest::where('status', 'approved')->count() +
            BreakRequest::where('status', 'approved')->count();

        // 勤怠申請を取得
        $attendanceRequests = AttendanceRequest::with('user')
            ->where('status', $status)
            ->get()
            ->map(function ($request) {
                $request->request_type = 'attendance';
                return $request;
            });

        // 休憩申請を取得
        $breakRequests = BreakRequest::with('user')
            ->where('status', $status)
            ->get()
            ->map(function ($request) {
                $request->request_type = 'break';
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

        return view('admin.requests', compact('paginator', 'status', 'pendingCount', 'approvedCount'));
    }

    /**
     * 勤怠一覧表示
     */
    public function attendances(Request $request)
    {
        // 日付パラメータを取得（デフォルトは今日）
        $date = $request->get('date', now()->format('Y-m-d'));
        $selectedDate = Carbon::parse($date);

        // 前日・翌日の日付を計算
        $prevDate = $selectedDate->copy()->subDay();
        $nextDate = $selectedDate->copy()->addDay();

        // その日の全ユーザーの勤怠データを取得
        $attendances = Attendance::with(['user', 'breakTimes'])
            ->whereDate('created_at', $selectedDate)
            ->orderBy('created_at', 'asc')
            ->get();

        // 全ユーザーを取得（勤怠データがないユーザーも含める）
        $allUsers = User::orderBy('name', 'asc')->get();

        // 勤怠データがないユーザーも含めて配列を作成
        $allAttendanceData = [];
        foreach ($allUsers as $user) {
            $attendance = $attendances->where('user_id', $user->id)->first();

            // 休憩時間を計算
            $breakTime = '';
            if ($attendance && $attendance->breakTimes->isNotEmpty()) {
                $totalBreakMinutes = 0;
                foreach ($attendance->breakTimes as $break) {
                    if ($break->start_time && $break->end_time) {
                        $totalBreakMinutes += $break->start_time->diffInMinutes($break->end_time);
                    }
                }
                $hours = floor($totalBreakMinutes / 60);
                $remainingMinutes = $totalBreakMinutes % 60;
                $breakTime = sprintf('%02d:%02d', $hours, $remainingMinutes);
            }

            // 勤務時間を計算
            $workTime = '';
            if ($attendance && $attendance->clock_in_time && $attendance->clock_out_time) {
                $clockIn = Carbon::parse($attendance->clock_in_time);
                $clockOut = Carbon::parse($attendance->clock_out_time);
                $totalMinutes = $clockOut->diffInMinutes($clockIn);

                // 休憩時間を差し引く
                if ($attendance->breakTimes->isNotEmpty()) {
                    $totalBreakMinutes = 0;
                    foreach ($attendance->breakTimes as $break) {
                        if ($break->start_time && $break->end_time) {
                            $totalBreakMinutes += $break->start_time->diffInMinutes($break->end_time);
                        }
                    }
                    $totalMinutes -= $totalBreakMinutes;
                }

                $hours = floor($totalMinutes / 60);
                $minutes = $totalMinutes % 60;
                $workTime = sprintf('%02d:%02d', $hours, $minutes);
            }

            $allAttendanceData[] = [
                'user' => $user,
                'attendance' => $attendance,
                'breakTime' => $breakTime,
                'workTime' => $workTime
            ];
        }

        return view('admin.attendances', compact(
            'allAttendanceData',
            'selectedDate',
            'prevDate',
            'nextDate'
        ));
    }

    /**
     * 勤務時間を計算
     */
    private function calculateWorkTime($clockIn, $clockOut, $breakTimes = [])
    {
        $totalMinutes = $clockOut->diffInMinutes($clockIn);

        // 休憩時間を差し引く
        foreach ($breakTimes as $break) {
            if ($break->start_time && $break->end_time) {
                $breakMinutes = $break->start_time->diffInMinutes($break->end_time);
                $totalMinutes -= $breakMinutes;
            }
        }

        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        return sprintf('%02d:%02d', $hours, $minutes);
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
        $remainingMinutes = $totalMinutes % 60;

        return sprintf('%02d:%02d', $hours, $remainingMinutes);
    }

    /**
     * 勤怠詳細表示
     */
    public function attendanceDetail($id, Request $request)
    {
        // IDが0の場合は新規作成ページとして扱う
        if ($id == 0) {
            // リクエストからユーザーIDと日付を取得
            $userId = $request->get('user_id');
            $date = $request->get('date', now()->format('Y-m-d'));

            $user = User::find($userId);
            $selectedDate = Carbon::parse($date);

            return view('admin.detail', [
                'attendance' => null,
                'user' => $user,
                'selectedDate' => $selectedDate
            ]);
        }

        $attendance = Attendance::with(['user', 'breakTimes'])->find($id);

        if (!$attendance) {
            abort(404, '勤怠データが見つかりません');
        }

        return view('admin.detail', [
            'attendance' => $attendance,
            'user' => $attendance->user,
            'selectedDate' => $attendance->created_at
        ]);
    }

    /**
     * 勤怠更新処理
     */
    public function attendanceUpdate(Request $request, $id)
    {
        $attendance = Attendance::findOrFail($id);

        $request->validate([
            'clock_in_time' => 'nullable|regex:/^[0-9]{1,2}:[0-9]{2}$/',
            'clock_out_time' => 'nullable|regex:/^[0-9]{1,2}:[0-9]{2}$/',
            'status' => 'required|in:working,break,completed',
            'notes' => 'nullable|string|max:255',
        ]);

        $updateData = [
            'status' => $request->status,
            'notes' => $request->notes,
        ];

        // 時間データの処理
        if ($request->clock_in_time) {
            $updateData['clock_in_time'] = $attendance->created_at->format('Y-m-d') . ' ' . $request->clock_in_time . ':00';
        }
        if ($request->clock_out_time) {
            $updateData['clock_out_time'] = $attendance->created_at->format('Y-m-d') . ' ' . $request->clock_out_time . ':00';
        }

        $attendance->update($updateData);

        return redirect()->route('admin.attendances')->with('success', '勤怠情報を更新しました。');
    }

    /**
     * 勤怠新規作成処理
     */
    public function attendanceStore(Request $request)
    {
        $request->validate([
            'clock_in_time' => 'nullable|regex:/^[0-9]{1,2}:[0-9]{2}$/',
            'clock_out_time' => 'nullable|regex:/^[0-9]{1,2}:[0-9]{2}$/',
            'status' => 'required|in:working,break,completed',
            'notes' => 'nullable|string|max:255',
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
        ]);

        // 指定された日付を使用
        $date = $request->date;

        $createData = [
            'user_id' => $request->user_id,
            'status' => $request->status,
            'notes' => $request->notes,
        ];

        // 時間データの処理
        if ($request->clock_in_time) {
            $createData['clock_in_time'] = $date . ' ' . $request->clock_in_time . ':00';
        }
        if ($request->clock_out_time) {
            $createData['clock_out_time'] = $date . ' ' . $request->clock_out_time . ':00';
        }

        Attendance::create($createData);

        return redirect()->route('admin.attendances')->with('success', '勤怠情報を作成しました。');
    }

    /**
     * 申請一覧を表示
     */
    public function attendanceRequests(Request $request)
    {
        $requests = AttendanceRequest::with(['user', 'attendance', 'approvedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.attendance-requests', compact('requests'));
    }

    /**
     * 申請詳細を表示
     */
    public function attendanceRequestDetail($id)
    {
        $request = AttendanceRequest::with(['user', 'attendance', 'approvedBy'])
            ->findOrFail($id);

        return view('admin.attendance-request-detail', compact('request'));
    }

    /**
     * 申請を承認
     */
    public function approveRequest(Request $request, $id)
    {
        $attendanceRequest = AttendanceRequest::findOrFail($id);

        if ($attendanceRequest->status !== 'pending') {
            return back()->withErrors(['general' => 'この申請は既に処理済みです。']);
        }

        // 承認処理
        $attendanceRequest->update([
            'status' => 'approved',
        ]);

        // 勤怠データを更新または作成
        if ($attendanceRequest->request_type === 'update') {
            // 修正申請の場合
            $attendance = $attendanceRequest->attendance;
            $updateData = [];

            if ($attendanceRequest->clock_in_time) {
                $updateData['clock_in_time'] = $attendanceRequest->target_date . ' ' . $attendanceRequest->clock_in_time;
            }
            if ($attendanceRequest->clock_out_time) {
                $updateData['clock_out_time'] = $attendanceRequest->target_date . ' ' . $attendanceRequest->clock_out_time;
            }

            $attendance->update($updateData);
        } else {
            // 新規作成申請の場合
            $createData = [
                'user_id' => $attendanceRequest->user_id,
            ];

            if ($attendanceRequest->clock_in_time) {
                $createData['clock_in_time'] = $attendanceRequest->target_date . ' ' . $attendanceRequest->clock_in_time;
            }
            if ($attendanceRequest->clock_out_time) {
                $createData['clock_out_time'] = $attendanceRequest->target_date . ' ' . $attendanceRequest->clock_out_time;
            }

            Attendance::create($createData);
        }

        return redirect()->route('admin.attendance.requests')
            ->with('success', '申請を承認しました。');
    }

    /**
     * 申請を却下
     */
    public function rejectRequest(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $attendanceRequest = AttendanceRequest::findOrFail($id);

        if ($attendanceRequest->status !== 'pending') {
            return back()->withErrors(['general' => 'この申請は既に処理済みです。']);
        }

        $attendanceRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        return redirect()->route('admin.attendance.requests')
            ->with('success', '申請を却下しました。');
    }

    /**
     * 休憩申請一覧を表示
     */
    public function breakRequests(Request $request)
    {
        $requests = BreakRequest::with(['user', 'break', 'attendance'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.break-requests', compact('requests'));
    }

    /**
     * 休憩申請詳細を表示
     */
    public function breakRequestDetail($id)
    {
        $request = BreakRequest::with(['user', 'break', 'attendance'])
            ->findOrFail($id);

        return view('admin.break-request-detail', compact('request'));
    }

    /**
     * 休憩申請を承認
     */
    public function approveBreakRequest(Request $request, $id)
    {
        $breakRequest = BreakRequest::findOrFail($id);

        if ($breakRequest->status !== 'pending') {
            return back()->withErrors(['general' => 'この申請は既に処理済みです。']);
        }

        // 承認処理
        $breakRequest->update([
            'status' => 'approved',
        ]);

        // 休憩データを更新または作成
        if ($breakRequest->request_type === 'update') {
            // 修正申請の場合
            $break = $breakRequest->break;
            $updateData = [];

            if ($breakRequest->start_time) {
                $updateData['start_time'] = $breakRequest->target_date . ' ' . $breakRequest->start_time;
            }
            if ($breakRequest->end_time) {
                $updateData['end_time'] = $breakRequest->target_date . ' ' . $breakRequest->end_time;
            }
            if ($breakRequest->notes) {
                $updateData['notes'] = $breakRequest->notes;
            }

            $break->update($updateData);
        } else {
            // 新規作成申請の場合
            $createData = [
                'attendance_id' => $breakRequest->attendance_id,
            ];

            if ($breakRequest->start_time) {
                $createData['start_time'] = $breakRequest->target_date . ' ' . $breakRequest->start_time;
            }
            if ($breakRequest->end_time) {
                $createData['end_time'] = $breakRequest->target_date . ' ' . $breakRequest->end_time;
            }
            if ($breakRequest->notes) {
                $createData['notes'] = $breakRequest->notes;
            }

            Breaktime::create($createData);
        }

        return redirect()->route('admin.break.requests')
            ->with('success', '休憩申請を承認しました。');
    }

    /**
     * 休憩申請を却下
     */
    public function rejectBreakRequest(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $breakRequest = BreakRequest::findOrFail($id);

        if ($breakRequest->status !== 'pending') {
            return back()->withErrors(['general' => 'この申請は既に処理済みです。']);
        }

        $breakRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        return redirect()->route('admin.break.requests')
            ->with('success', '休憩申請を却下しました。');
    }
}
