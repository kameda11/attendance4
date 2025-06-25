@extends('layouts.app')
<link rel="stylesheet" href="{{ asset('css/admin/detail.css') }}">

@section('content')
<div class="attendance-detail-container">
    <div class="attendance-detail-header">
        <h1>勤怠詳細</h1>
        <a href="{{ route('admin.attendances') }}" class="back-button">勤怠一覧に戻る</a>
    </div>

    <form action="{{ $attendance ? route('admin.attendance.update', ['id' => $attendance->id]) : route('admin.attendance.store') }}" method="POST">
        @csrf
        @if($attendance)
        @method('PUT')
        @else
        <input type="hidden" name="user_id" value="{{ $user ? $user->id : '' }}">
        <input type="hidden" name="date" value="{{ $selectedDate ? $selectedDate->format('Y-m-d') : '' }}">
        @endif
        <div class="attendance-detail-table">
            <table>
                <tbody>
                    <tr>
                        <th>名前</th>
                        <td>{{ $user ? $user->name : '未選択' }}</td>
                    </tr>
                    <tr>
                        <th>日付</th>
                        <td>{{ $attendance ? $attendance->created_at->format('Y年m月d日') . '(' . $attendance->created_at->format('D') . ')' : ($selectedDate ? $selectedDate->format('Y年m月d日') . '(' . $selectedDate->format('D') . ')' : '未設定') }}</td>
                    </tr>
                    <tr>
                        <th>出勤・退勤</th>
                        <td>
                            <div class="time-inputs">
                                <div class="time-input">
                                    <input type="text" name="clock_in_time" pattern="[0-9]{1,2}:[0-9]{2}" maxlength="5" value="{{ $attendance && $attendance->clock_in_time ? $attendance->clock_in_time->format('H:i') : '' }}" inputmode="numeric" autocomplete="off">
                                </div>
                                <label>~</label>
                                <div class="time-input">
                                    <input type="text" name="clock_out_time" pattern="[0-9]{1,2}:[0-9]{2}" maxlength="5" value="{{ $attendance && $attendance->clock_out_time ? $attendance->clock_out_time->format('H:i') : '' }}" inputmode="numeric" autocomplete="off">
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th>休憩</th>
                        <td>
                            <div class="time-inputs">
                                <div class="time-input">
                                    <input type="text" name="break_start_time" pattern="[0-9]{1,2}:[0-9]{2}" maxlength="5" value="{{ $attendance && $attendance->break_start_time ? $attendance->break_start_time->format('H:i') : '' }}" inputmode="numeric" autocomplete="off">
                                </div>
                                <label>~</label>
                                <div class="time-input">
                                    <input type="text" name="break_end_time" pattern="[0-9]{1,2}:[0-9]{2}" maxlength="5" value="{{ $attendance && $attendance->break_end_time ? $attendance->break_end_time->format('H:i') : '' }}" inputmode="numeric" autocomplete="off">
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th>休憩2</th>
                        <td>
                            <div class="time-inputs">
                                <div class="time-input">
                                    <input type="text" name="break2_start_time" pattern="[0-9]{1,2}:[0-9]{2}" maxlength="5" value="{{ $attendance && $attendance->break2_start_time ? $attendance->break2_start_time->format('H:i') : '' }}" inputmode="numeric" autocomplete="off">
                                </div>
                                <label>~</label>
                                <div class="time-input">
                                    <input type="text" name="break2_end_time" pattern="[0-9]{1,2}:[0-9]{2}" maxlength="5" value="{{ $attendance && $attendance->break2_end_time ? $attendance->break2_end_time->format('H:i') : '' }}" inputmode="numeric" autocomplete="off">
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>

                    <tr>
                        <th>備考</th>
                        <td>
                            <input type="text" name="notes" class="notes-textbox" value="{{ $attendance ? ($attendance->notes ?? '') : '' }}">
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="attendance-detail-actions">
            <button type="submit" class="btn btn-primary">修正</button>
        </div>
    </form>
</div>
@endsection