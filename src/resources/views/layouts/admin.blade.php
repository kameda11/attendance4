<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>coachtech - 管理者</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    @yield('css')
</head>

<body class="admin-body">
    <header class="header admin-header">
        <div class="header__inner">
            <div class="header-utilities">
                <a class="header__logo">
                    <img src="{{ asset('storage/logo.svg') }}" alt="coachtech">
                    <span class="admin-badge">管理者</span>
                </a>
                <nav>
                    <ul class="header-nav">
                        <li class="header-nav__item">
                            <a class="header-nav__link" href="/admin/attendances">勤怠一覧</a>
                        </li>
                        <li class="header-nav__item">
                            <a class="header-nav__link" href="/admin/users">スタッフ一覧</a>
                        </li>
                        <li class="header-nav__item">
                            <a class="header-nav__link" href="/admin/requests">申請一覧</a>
                        </li>
                        <li class="header-nav__item">
                            <form class="form" action="/admin/logout" method="post">
                                @csrf
                                <button class="header-nav__button">ログアウト</button>
                            </form>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <main class="admin-main">
        @yield('content')
        @yield('script')
    </main>
</body>

</html>