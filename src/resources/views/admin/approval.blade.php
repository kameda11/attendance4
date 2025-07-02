@extends('layouts.admin')
<link rel="stylesheet" href="{{ asset('css/admin/approval.css') }}">

@section('content')
<div class="attendance-detail-container">
    <div class="attendance-detail-header">
        <h1>修正申請承認</h1>
    </div>

    <div class="attendance-detail-table">
        <table>
            <tbody>
                <tr>
                    <th>名前</th>
                    <td>{{ $request->user->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>日付</th>
                    <td>{{ $request->attendance ? $request->attendance->created_at->format('Y年m月d日') : ($request->target_date ? $request->target_date->format('Y年m月d日') : '未設定') }}</td>
                </tr>
                <tr>
                    <th>出勤・退勤</th>
                    <td>
                        @if($request->clock_in_time || $request->clock_out_time)
                        <span class="time-display">
                            {{ $request->clock_in_time ? \Carbon\Carbon::parse($request->clock_in_time)->format('H:i') : '' }} ~ {{ $request->clock_out_time ? \Carbon\Carbon::parse($request->clock_out_time)->format('H:i') : '' }}
                        </span>
                        @else
                        <span class="no-data">未設定</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>休憩</th>
                    <td>
                        @if($request->attendance && $request->attendance->breakTimes->count() > 0)
                        @php $firstBreak = $request->attendance->breakTimes->first(); @endphp
                        <div class="break-item">
                            <span class="break-time">
                                {{ $firstBreak->start_time ? \Carbon\Carbon::parse($firstBreak->start_time)->format('H:i') : '' }} ~ {{ $firstBreak->end_time ? \Carbon\Carbon::parse($firstBreak->end_time)->format('H:i') : '' }}
                            </span>
                            @if($firstBreak->notes)
                            <span class="break-notes">({{ $firstBreak->notes }})</span>
                            @endif
                        </div>
                        @else
                        <span class="no-data"></span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>休憩2</th>
                    <td>
                        @if($request->attendance && $request->attendance->breakTimes->count() > 1)
                        @php $secondBreak = $request->attendance->breakTimes->get(1); @endphp
                        <div class="break-item">
                            <span class="break-time">
                                {{ $secondBreak->start_time ? \Carbon\Carbon::parse($secondBreak->start_time)->format('H:i') : '' }} ~ {{ $secondBreak->end_time ? \Carbon\Carbon::parse($secondBreak->end_time)->format('H:i') : '' }}
                            </span>
                            @if($secondBreak->notes)
                            <span class="break-notes">({{ $secondBreak->notes }})</span>
                            @endif
                        </div>
                        @else
                        <span class="no-data"></span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>備考</th>
                    <td>
                        @if($request->notes)
                        <div class="notes-content">{{ $request->notes }}</div>
                        @else
                        <span class="no-data"></span>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="attendance-detail-actions">
        @if($request->status === 'pending')
        <form action="{{ route('admin.attendance.request.approve', ['id' => $request->id]) }}" method="POST" style="display: inline;">
            @csrf
            <button type="submit" class="btn btn-primary">承認する</button>
        </form>
        @else
        <button class="btn btn-secondary" disabled>
            @if($request->status === 'approved')
            承認済み
            @elseif($request->status === 'rejected')
            却下済み
            @else
            処理済み
            @endif
        </button>
        @endif
    </div>
</div>


@endsection