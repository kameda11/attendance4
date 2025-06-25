<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Admin;
use App\Models\Attendance;
use App\Models\User;
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

            return redirect()->intended(route('admin.attendances'));
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
        $attendances = Attendance::with('user')
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
            if ($attendance && $attendance->break_start_time && $attendance->break_end_time) {
                $breakStart = Carbon::parse($attendance->break_start_time);
                $breakEnd = Carbon::parse($attendance->break_end_time);
                $minutes = $breakEnd->diffInMinutes($breakStart);
                $hours = floor($minutes / 60);
                $remainingMinutes = $minutes % 60;
                $breakTime = sprintf('%02d:%02d', $hours, $remainingMinutes);
            }

            // 勤務時間を計算
            $workTime = '';
            if ($attendance && $attendance->clock_in_time && $attendance->clock_out_time) {
                $clockIn = Carbon::parse($attendance->clock_in_time);
                $clockOut = Carbon::parse($attendance->clock_out_time);
                $totalMinutes = $clockOut->diffInMinutes($clockIn);

                if ($attendance->break_start_time && $attendance->break_end_time) {
                    $breakStart = Carbon::parse($attendance->break_start_time);
                    $breakEnd = Carbon::parse($attendance->break_end_time);
                    $breakMinutes = $breakEnd->diffInMinutes($breakStart);
                    $totalMinutes -= $breakMinutes;
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
    private function calculateWorkTime($clockIn, $clockOut, $breakStart = null, $breakEnd = null)
    {
        $totalMinutes = $clockOut->diffInMinutes($clockIn);

        // 休憩時間を差し引く
        if ($breakStart && $breakEnd) {
            $breakMinutes = $breakEnd->diffInMinutes($breakStart);
            $totalMinutes -= $breakMinutes;
        }

        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        return sprintf('%02d:%02d', $hours, $minutes);
    }

    /**
     * 休憩時間を計算
     */
    private function calculateBreakTime($breakStart, $breakEnd)
    {
        $minutes = $breakEnd->diffInMinutes($breakStart);
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

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

        $attendance = Attendance::with('user')->find($id);

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
            'break_start_time' => 'nullable|regex:/^[0-9]{1,2}:[0-9]{2}$/',
            'break_end_time' => 'nullable|regex:/^[0-9]{1,2}:[0-9]{2}$/',
            'break2_start_time' => 'nullable|regex:/^[0-9]{1,2}:[0-9]{2}$/',
            'break2_end_time' => 'nullable|regex:/^[0-9]{1,2}:[0-9]{2}$/',
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
        if ($request->break_start_time) {
            $updateData['break_start_time'] = $attendance->created_at->format('Y-m-d') . ' ' . $request->break_start_time . ':00';
        }
        if ($request->break_end_time) {
            $updateData['break_end_time'] = $attendance->created_at->format('Y-m-d') . ' ' . $request->break_end_time . ':00';
        }
        if ($request->break2_start_time) {
            $updateData['break2_start_time'] = $attendance->created_at->format('Y-m-d') . ' ' . $request->break2_start_time . ':00';
        }
        if ($request->break2_end_time) {
            $updateData['break2_end_time'] = $attendance->created_at->format('Y-m-d') . ' ' . $request->break2_end_time . ':00';
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
            'break_start_time' => 'nullable|regex:/^[0-9]{1,2}:[0-9]{2}$/',
            'break_end_time' => 'nullable|regex:/^[0-9]{1,2}:[0-9]{2}$/',
            'break2_start_time' => 'nullable|regex:/^[0-9]{1,2}:[0-9]{2}$/',
            'break2_end_time' => 'nullable|regex:/^[0-9]{1,2}:[0-9]{2}$/',
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
        if ($request->break_start_time) {
            $createData['break_start_time'] = $date . ' ' . $request->break_start_time . ':00';
        }
        if ($request->break_end_time) {
            $createData['break_end_time'] = $date . ' ' . $request->break_end_time . ':00';
        }
        if ($request->break2_start_time) {
            $createData['break2_start_time'] = $date . ' ' . $request->break2_start_time . ':00';
        }
        if ($request->break2_end_time) {
            $createData['break2_end_time'] = $date . ' ' . $request->break2_end_time . ':00';
        }

        Attendance::create($createData);

        return redirect()->route('admin.attendances')->with('success', '勤怠情報を作成しました。');
    }
}
