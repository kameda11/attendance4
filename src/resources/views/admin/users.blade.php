@extends('layouts.admin')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin/users.css') }}">

<div class="admin-users-container">
    <div class="header-content">
        <h1>スタッフ一覧</h1>
    </div>

    <main class="users-main">
        <div class="users-table">
            <table>
                <thead>
                    <tr>
                        <th>名前</th>
                        <th>メールアドレス</th>
                        <th>詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr>
                        <td class="user-name">
                            <div class="user-info">
                                <span class="name">{{ $user->name }}</span>
                            </div>
                        </td>
                        <td class="user-email">{{ $user->email }}</td>
                        <td>
                            <a href="{{ route('admin.user.attendance.list', ['userId' => $user->id]) }}" class="action-button detail">詳細</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="no-users">ユーザーが登録されていません</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>
</div>
@endsection