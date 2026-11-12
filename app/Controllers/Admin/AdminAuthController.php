<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\AccountService;

final class AdminAuthController
{
    public function loginForm(): string
    {
        if (Auth::check() && Auth::isAdmin()) {
            Response::redirect(url('/admin'));
        }
        return View::render('admin/login', ['title' => 'Admin sign-in'], 'layouts/blank');
    }

    public function login(): never
    {
        Csrf::require();
        $email = (string) Request::input('email', '');
        $password = (string) Request::post('password', '');
        $result = (new AccountService())->authenticate($email, $password);
        if (!$result['ok']) {
            Session::set('_errors', ['email' => $result['message']]);
            remember_input(['email' => $email]);
            Response::redirect(url('/admin/login'));
        }
        if (!Auth::isAdmin()) {
            (new AccountService())->logout();
            Session::set('_errors', ['email' => 'This account does not have admin access.']);
            remember_input(['email' => $email]);
            Response::redirect(url('/admin/login'));
        }
        clear_input();
        Session::regenerate();
        flash('success', 'Signed in to the admin dashboard.');
        Response::redirect(url('/admin'));
    }

    public function logout(): never
    {
        Csrf::require();
        (new AccountService())->logout();
        Response::redirect(url('/admin/login'));
    }
}
