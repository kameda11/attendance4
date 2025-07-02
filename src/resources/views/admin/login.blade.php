@extends('layouts.admin-login')
<link rel="stylesheet" href="{{ asset('css/admin/login.css') }}">

@section('content')
<div class="login-container">
    <div class="login-box">
        <div class="login-header">
            <h1>管理者ログイン</h1>
        </div>

        <form method="POST" action="{{ route('admin.login') }}" class="login-form">
            @csrf

            <div class="form-group">
                <label for="email">メールアドレス</label>
                <input type="email" id="email" name="email">
                @error('email')
                <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">パスワード</label>
                <input type="password" id="password" name="password">
                @error('password')
                <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <button type="submit" class="login-button">管理者ログインする</button>
            </div>
        </form>
    </div>
</div>
@endsection