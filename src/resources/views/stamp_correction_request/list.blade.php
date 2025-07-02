@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/stamp-correction-request-list.css') }}">

<div class="admin-requests-container">
    <div class="header-content">
        <h1>申請一覧</h1>
    </div>

    <div class="tab-container">
        <div class="tab-buttons">
            <button class="tab-button {{ $status === 'pending' ? 'active' : '' }}" onclick="changeTab('pending')">
                承認待ち ({{ $pendingCount }})
            </button>
            <button class="tab-button {{ $status === 'approved' ? 'active' : '' }}" onclick="changeTab('approved')">
                承認済み ({{ $approvedCount }})
            </button>
        </div>
    </div>

    <main class="requests-main">
        <div class="requests-table">
            <table>
                <thead>
                    <tr>
                        <th>状態</th>
                        <th>名前</th>
                        <th>対象日時</th>
                        <th>申請理由</th>
                        <th>申請日時</th>
                        <th>詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($paginator as $request)
                    <tr class="request-row {{ $request->status }}">
                        <td class="status-cell">
                            <span class="status-badge {{ $request->status }}">
                                @if($request->status === 'pending')
                                申請待ち
                                @elseif($request->status === 'approved')
                                承認済み
                                @else
                                {{ $request->status }}
                                @endif
                            </span>
                        </td>
                        <td class="user-name">{{ $request->user ? $request->user->name : '不明' }}</td>
                        <td class="target-date">{{ $request->target_date->format('Y年m月d日') }}</td>
                        <td class="request-reason">
                            @if($request->notes)
                            {{ Str::limit($request->notes, 30) }}
                            @else
                            <span class="no-reason">理由なし</span>
                            @endif
                        </td>
                        <td class="request-date">{{ $request->created_at->format('Y年m月d日 H:i') }}</td>
                        <td>
                            @if($request->request_type === 'attendance')
                            <a href="{{ route('admin.attendance.detail', ['id' => $request->attendance_id, 'user_id' => $request->user_id, 'date' => $request->target_date->format('Y-m-d')]) }}" class="action-button detail">詳細</a>
                            @elseif($request->request_type === 'break')
                            <a href="{{ route('admin.attendance.detail', ['id' => $request->attendance_id, 'user_id' => $request->user_id, 'date' => $request->target_date->format('Y-m-d')]) }}" class="action-button detail">詳細</a>
                            @else
                            <span class="action-button detail disabled">詳細</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="no-requests">
                            @if($status === 'pending')
                            承認待ちの申請がありません
                            @else
                            承認済みの申請がありません
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($paginator->hasPages())
        <div class="pagination-container">
            {{ $paginator->links() }}
        </div>
        @endif
    </main>
</div>

<script>
    function changeTab(status) {
        const currentUrl = new URL(window.location);
        currentUrl.searchParams.set('status', status);
        window.location.href = currentUrl.toString();
    }
</script>
@endsection