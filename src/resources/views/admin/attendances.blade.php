@extends('layouts.admin')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin/attendances.css') }}">

<div class="admin-attendances-container">
    <div class="header-content">
        <h1>勤怠一覧</h1>
    </div>

    <div class="date-navigation">
        <div class="nav-content">
            <a href="{{ route('admin.attendances', ['date' => $prevDate->format('Y-m-d')]) }}" class="nav-button prev">
                <img src="{{ asset('storage/arrow.png') }}" alt="前日" class="arrow-left">
                前日
            </a>

            <div class="current-date">
                <h2>{{ $selectedDate->format('Y年m月d日') }} ({{ $selectedDate->isoFormat('ddd') }})</h2>
            </div>
            <a href="{{ route('admin.attendances', ['date' => $nextDate->format('Y-m-d')]) }}" class="nav-button next">
                翌日
                <img src="{{ asset('storage/arrow.png') }}" alt="翌日" class="arrow-right">
            </a>
        </div>
    </div>

    <main class="attendances-main">
        <div class="attendances-table">
            <table>
                <thead>
                    <tr>
                        <th>名前</th>
                        <th>出勤</th>
                        <th>退勤</th>
                        <th>休憩</th>
                        <th>合計</th>
                        <th>詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($allAttendanceData as $data)
                    <tr class="{{ $data['attendance'] ? 'has-attendance' : 'no-attendance' }}">
                        <td class="user-name">
                            <div class="user-info">
                                <span class="name">{{ $data['user']->name }}</span>
                            </div>
                        </td>
                        <td>{{ $data['attendance'] && $data['attendance']->clock_in_time ? $data['attendance']->clock_in_time->format('H:i') : '' }}</td>
                        <td>{{ $data['attendance'] && $data['attendance']->clock_out_time ? $data['attendance']->clock_out_time->format('H:i') : '' }}</td>
                        <td>{{ $data['breakTime'] }}</td>
                        <td>{{ $data['workTime'] }}</td>
                        <td>
                            @if($data['attendance'])
                            <a href="{{ route('admin.attendance.detail', ['id' => $data['attendance']->id]) }}" class="action-button detail">詳細</a>
                            @else
                            <a href="{{ route('admin.attendance.detail', ['id' => 0, 'user_id' => $data['user']->id, 'date' => $selectedDate->format('Y-m-d')]) }}" class="action-button detail">詳細</a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="no-users">ユーザーが登録されていません</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>
</div>
@endsection