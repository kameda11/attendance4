@extends('layouts.app')
<link rel="stylesheet" href="{{ asset('css/attendance/detail.css') }}">

@section('content')
<div class="attendance-detail-container">
    <div class="attendance-detail-header">
        <h1>勤怠詳細</h1>
    </div>

    <form action="{{ route('user.attendance.update', ['id' => $attendance ? $attendance->id : 0]) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="attendance-detail-table">
            <table>
                <tbody>
                    <tr>
                        <th>名前</th>
                        <td>{{ Auth::user()->name }}</td>
                    </tr>
                    <tr>
                        <th>日付</th>
                        <td>
                            @if($attendance)
                            {{ $attendance->created_at->format('Y年m月d日') }}({{ $attendance->created_at->format('D') }})
                            @else
                            {{ $date ? \Carbon\Carbon::parse($date)->format('Y年m月d日') : '未設定' }}
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>出勤・退勤</th>
                        <td>
                            @if($attendance)
                            <div class="time-inputs">
                                <div class="time-input">
                                    <input type="text" name="clock_in_time" pattern="[0-9]{1,2}:[0-9]{2}" maxlength="5" value="{{ $attendance->clock_in_time ? $attendance->clock_in_time->format('H:i') : '' }}" inputmode="numeric" autocomplete="off">
                                </div>
                                <label>~</label>
                                <div class="time-input">
                                    <input type="text" name="clock_out_time" pattern="[0-9]{1,2}:[0-9]{2}" maxlength="5" value="{{ $attendance->clock_out_time ? $attendance->clock_out_time->format('H:i') : '' }}" inputmode="numeric" autocomplete="off">
                                </div>
                            </div>
                            @else
                            勤怠記録なし
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>休憩</th>
                        <td>
                            @if($attendance)
                            <div class="time-inputs">
                                <div class="time-input">
                                    <input type="text" name="break_start_time" pattern="[0-9]{1,2}:[0-9]{2}" maxlength="5" value="{{ $attendance->break_start_time ? $attendance->break_start_time->format('H:i') : '' }}" inputmode="numeric" autocomplete="off">
                                </div>
                                <label>~</label>
                                <div class="time-input">
                                    <input type="text" name="break_end_time" pattern="[0-9]{1,2}:[0-9]{2}" maxlength="5" value="{{ $attendance->break_end_time ? $attendance->break_end_time->format('H:i') : '' }}" inputmode="numeric" autocomplete="off">
                                </div>
                            </div>
                            @else
                            -
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>休憩2</th>
                        <td>
                            @if($attendance)
                            <div class="time-inputs">
                                <div class="time-input">
                                    <input type="text" name="break2_start_time" pattern="[0-9]{1,2}:[0-9]{2}" maxlength="5" value="{{ $attendance->break2_start_time ? $attendance->break2_start_time->format('H:i') : '' }}" inputmode="numeric" autocomplete="off">
                                </div>
                                <label>~</label>
                                <div class="time-input">
                                    <input type="text" name="break2_end_time" pattern="[0-9]{1,2}:[0-9]{2}" maxlength="5" value="{{ $attendance->break2_end_time ? $attendance->break2_end_time->format('H:i') : '' }}" inputmode="numeric" autocomplete="off">
                                </div>
                            </div>
                            @else
                            -
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>備考</th>
                        <td>
                            @if($attendance)
                            <input type="text" name="status" class="status-textbox" value="{{ $attendance->status }}">
                            @else
                            勤怠記録なし
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        @if($attendance)
        <div class="attendance-detail-actions">
            <button type="submit" class="btn btn-primary">修正</button>
        </div>
        @endif
    </form>
</div>
@endsection