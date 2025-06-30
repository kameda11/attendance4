@extends('layouts.app')
<link rel="stylesheet" href="{{ asset('css/attendance/list.css') }}">

@section('content')
<div class="attendance-list-container">
    <div class="attendance-list-header">
        <h1>勤怠一覧</h1>
        <div class="month-navigation">
            <a href="{{ route('user.attendance.list', ['year' => $prevMonth->year, 'month' => $prevMonth->month]) }}" class="btn btn-secondary">
                <img src="{{ asset('storage/arrow.png') }}" alt="前月" class="arrow-left">前月
            </a>
            <div class="month-selector">
                <select id="monthSelect" onchange="changeMonth()">
                    @for($year = 2020; $year <= 2030; $year++)
                        <optgroup label="{{ $year }}年">
                        @for($month = 1; $month <= 12; $month++)
                            <option value="{{ $year }}/{{ $month }}"
                            {{ $currentMonth->year == $year && $currentMonth->month == $month ? 'selected' : '' }}>
                            {{ $month }}月
                            </option>
                            @endfor
                            </optgroup>
                            @endfor
                </select>
                <span class="current-month">{{ $currentMonth->format('Y/m') }}</span>
            </div>
            <a href="{{ route('user.attendance.list', ['year' => $nextMonth->year, 'month' => $nextMonth->month]) }}" class="btn btn-secondary">翌月
                <img src="{{ asset('storage/arrow.png') }}" alt="翌月" class="arrow-right">
            </a>
        </div>
    </div>

    <div class="attendance-table">
        <table>
            <thead>
                <tr>
                    <th>日付</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach($calendar as $date)
                <tr class="{{ $date['isToday'] ? 'today' : '' }} {{ $date['isWeekend'] ? 'weekend' : '' }}">
                    <td>{{ $currentMonth->format('n') }}/{{ $date['day'] }}({{ $date['weekday'] }})</td>
                    <td>{{ $date['attendance'] && $date['attendance']->clock_in_time ? $date['attendance']->clock_in_time->format('H:i') : '' }}</td>
                    <td>{{ $date['attendance'] && $date['attendance']->clock_out_time ? $date['attendance']->clock_out_time->format('H:i') : '' }}</td>
                    <td>{{ $date['breakTime'] }}</td>
                    <td>{{ $date['workTime'] }}</td>
                    <td>
                        @if($date['attendance'])
                        <a href="{{ route('user.attendance.detail', ['id' => $date['attendance']->id]) }}">詳細</a>
                        @else
                        <a href="{{ route('user.attendance.detail', ['id' => 0, 'date' => $date['date']]) }}">詳細</a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('script')
<script>
    function changeMonth() {
        const select = document.getElementById('monthSelect');
        const [year, month] = select.value.split('/');
        window.location.href = `{{ route('user.attendance.list') }}?year=${year}&month=${month}`;
    }
</script>
@endsection