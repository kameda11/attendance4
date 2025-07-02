@extends('layouts.app')
<link rel="stylesheet" href="{{ asset('css/attendance/detail.css') }}">

@section('content')
<div class="attendance-detail-container">
    <div class="attendance-detail-header">
        <h1>勤怠詳細</h1>
    </div>

    @if($attendance)
    <div class="attendance-notice">
        <p>※ 修正内容は承認申請として送信されます。承認をお待ちください。</p>
    </div>
    @endif

    <form action="{{ $attendance ? route('user.attendance.update', ['id' => $attendance->id]) : route('user.attendance.store') }}" method="POST">
        @csrf
        @if($attendance)
        @method('PUT')
        @endif

        @if(!$attendance)
        <input type="hidden" name="date" value="{{ $date }}">
        @endif

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
                            <div class="time-inputs">
                                <div class="time-input">
                                    <input type="text" name="clock_in_time" pattern="[0-9]{1,2}:[0-9]{2}" maxlength="5" value="{{ old('clock_in_time', $attendance && $attendance->clock_in_time ? $attendance->clock_in_time->format('H:i') : '') }}" inputmode="numeric" autocomplete="off">
                                </div>
                                <label>~</label>
                                <div class="time-input">
                                    <input type="text" name="clock_out_time" pattern="[0-9]{1,2}:[0-9]{2}" maxlength="5" value="{{ old('clock_out_time', $attendance && $attendance->clock_out_time ? $attendance->clock_out_time->format('H:i') : '') }}" inputmode="numeric" autocomplete="off">
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th>休憩</th>
                        <td>
                            @if($attendance && $attendance->breakTimes->count() > 0)
                            @php $firstBreak = $attendance->breakTimes->first(); @endphp
                            <div class="break-item">
                                <span class="break-time">
                                    {{ $firstBreak->start_time ? $firstBreak->start_time->format('H:i') : '' }} ~
                                    {{ $firstBreak->end_time ? $firstBreak->end_time->format('H:i') : '' }}
                                </span>
                                @if($firstBreak->notes)
                                <span class="break-notes">({{ $firstBreak->notes }})</span>
                                @endif
                            </div>
                            @else
                            <div class="time-inputs">
                                <div class="time-input">
                                    <input type="text" name="break1_start_time" pattern="[0-9]{1,2}:[0-9]{2}" maxlength="5" value="{{ old('break1_start_time') }}" inputmode="numeric" autocomplete="off">
                                </div>
                                <label>~</label>
                                <div class="time-input">
                                    <input type="text" name="break1_end_time" pattern="[0-9]{1,2}:[0-9]{2}" maxlength="5" value="{{ old('break1_end_time') }}" inputmode="numeric" autocomplete="off">
                                </div>
                            </div>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>休憩2</th>
                        <td>
                            @if($attendance && $attendance->breakTimes->count() > 1)
                            @php $secondBreak = $attendance->breakTimes->get(1); @endphp
                            <div class="break-item">
                                <span class="break-time">
                                    {{ $secondBreak->start_time ? $secondBreak->start_time->format('H:i') : '' }} ~
                                    {{ $secondBreak->end_time ? $secondBreak->end_time->format('H:i') : '' }}
                                </span>
                                @if($secondBreak->notes)
                                <span class="break-notes">({{ $secondBreak->notes }})</span>
                                @endif
                            </div>
                            @else
                            <div class="time-inputs">
                                <div class="time-input">
                                    <input type="text" name="break2_start_time" pattern="[0-9]{1,2}:[0-9]{2}" maxlength="5" value="{{ old('break2_start_time') }}" inputmode="numeric" autocomplete="off">
                                </div>
                                <label>~</label>
                                <div class="time-input">
                                    <input type="text" name="break2_end_time" pattern="[0-9]{1,2}:[0-9]{2}" maxlength="5" value="{{ old('break2_end_time') }}" inputmode="numeric" autocomplete="off">
                                </div>
                            </div>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>備考</th>
                        <td>
                            <textarea name="notes" class="notes-textarea">{{ old('notes', $attendance ? $attendance->notes : '') }}</textarea>
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