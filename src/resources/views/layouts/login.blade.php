<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>coachtech - ログイン</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    @yield('css')
</head>

<body class="login-body">
    <header class="header login-header">
        <div class="header__inner">
            <div class="header-utilities">
                <a class="header__logo">
                    <img src="{{ asset('storage/logo.svg') }}" alt="coachtech">
                </a>
            </div>
        </div>
    </header>

    <main class="login-main">
        @yield('content')
        @yield('script')
    </main>
</body>

</html>