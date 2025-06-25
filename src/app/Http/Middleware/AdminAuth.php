<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 管理者ログイン状態をチェック
        if (!$request->session()->has('admin_logged_in') || !$request->session()->get('admin_logged_in')) {
            return redirect()->route('admin.login.form');
        }

        return $next($request);
    }
}
