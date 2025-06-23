<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Attendance;

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
     * プロフィール表示
     */
    public function profile()
    {
        $user = Auth::user();
        return view('user.profile', compact('user'));
    }

    /**
     * プロフィール更新
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        return redirect()->route('user.profile')
            ->with('success', 'プロフィールを更新しました。');
    }

    /**
     * パスワード変更
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();
        $user->update([
            'password' => bcrypt($request->password),
        ]);

        return redirect()->route('user.profile')
            ->with('success', 'パスワードを変更しました。');
    }
}
