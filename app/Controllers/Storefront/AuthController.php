<?php

declare(strict_types=1);

namespace App\Controllers\Storefront;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Services\AccountService;

final class AuthController
{
    public function loginForm(): string
    {
        if (Auth::check()) {
            Response::redirect(url(Auth::isAdmin() ? '/admin' : '/account'));
        }
        $next = (string) Request::query('next', '');
        if ($next !== '' && (!str_starts_with($next, '/') || str_starts_with($next, '//'))) {
            $next = '';
        }
        if (str_starts_with($next, '/dealer') && !\App\Services\DealerService::isProgramEnabled()) {
            $next = '';
        }
        return View::render('storefront/auth/login', [
            'pageTitle' => 'Sign in',
            'metaDescription' => 'Sign in to your Thunder Mounts account to track orders and manage your details.',
            'robots' => 'noindex,follow',
            'next' => $next,
        ], 'layouts/storefront');
    }

    public function login(): never
    {
        Csrf::require();
        $email = (string) Request::input('email', '');
        $password = (string) Request::post('password', '');
        $next = (string) Request::post('next', '');
        if ($next === '' || !str_starts_with($next, '/') || str_starts_with($next, '//')) {
            $next = '/account';
        }
        if (str_starts_with($next, '/dealer') && !\App\Services\DealerService::isProgramEnabled()) {
            $next = '/account';
        }

        $validator = (new Validator(['email' => $email, 'password' => $password], ['email' => 'Email address', 'password' => 'Password']))
            ->validate(['email' => 'required|email', 'password' => 'required']);
        if ($validator->fails()) {
            Session::set('_errors', $validator->errors());
            remember_input(['email' => $email]);
            Response::redirect(url('/login?next=' . urlencode($next)));
        }

        $result = (new AccountService())->authenticate($email, $password);
        if (!$result['ok']) {
            Session::set('_errors', ['email' => $result['message']]);
            remember_input(['email' => $email]);
            Response::redirect(url('/login?next=' . urlencode($next)));
        }

        clear_input();
        flash('success', 'Welcome back, ' . ($result['user']['first_name'] ?: 'there') . '.');
        if (str_starts_with($next, '/admin')) {
            $next = Auth::isAdmin() ? $next : '/account';
        }
        Response::redirect(url($next));
    }

    public function registerForm(): string
    {
        if (Auth::check()) {
            Response::redirect(url('/account'));
        }
        return View::render('storefront/auth/register', [
            'pageTitle' => 'Create your account',
            'metaDescription' => 'Create a Thunder Mounts account to check out faster and track your orders.',
            'robots' => 'noindex,follow',
        ], 'layouts/storefront');
    }

    public function register(): never
    {
        Csrf::require();
        $input = [
            'first_name' => (string) Request::input('first_name', ''),
            'last_name' => (string) Request::input('last_name', ''),
            'email' => (string) Request::input('email', ''),
            'phone' => (string) Request::input('phone', ''),
            'company' => (string) Request::input('company', ''),
            'password' => (string) Request::post('password', ''),
            'password_confirmation' => (string) Request::post('password_confirmation', ''),
        ];
        if (!Request::bool('accept_terms')) {
            Session::set('_errors', ['accept_terms' => 'Please accept the terms and privacy policy.']);
            remember_input($input);
            Response::redirect(url('/register'));
        }
        $result = (new AccountService())->register($input, true);
        if (!$result['ok']) {
            Session::set('_errors', $result['errors']);
            remember_input($input);
            Response::redirect(url('/register'));
        }
        clear_input();
        flash('success', 'Your account is ready. Welcome to Thunder Mounts.');
        Response::redirect(url('/account'));
    }

    public function logout(): never
    {
        Csrf::require();
        (new AccountService())->logout();
        flash('success', 'You have been signed out.');
        Response::redirect(url('/'));
    }

    public function forgotForm(): string
    {
        return View::render('storefront/auth/forgot', [
            'pageTitle' => 'Reset your password',
            'robots' => 'noindex,follow',
        ], 'layouts/storefront');
    }

    public function forgot(): never
    {
        Csrf::require();
        $email = (string) Request::input('email', '');
        $validator = (new Validator(['email' => $email], ['email' => 'Email address']))->validate(['email' => 'required|email']);
        if ($validator->fails()) {
            Session::set('_errors', $validator->errors());
            remember_input(['email' => $email]);
            Response::redirect(url('/forgot-password'));
        }
        $result = (new AccountService())->requestPasswordReset($email);
        clear_input();
        flash('success', $result['message']);
        Response::redirect(url('/forgot-password'));
    }

    public function resetForm(): string
    {
        return View::render('storefront/auth/reset', [
            'pageTitle' => 'Choose a new password',
            'robots' => 'noindex,nofollow',
            'token' => (string) Request::query('token', ''),
            'email' => (string) Request::query('email', ''),
        ], 'layouts/storefront');
    }

    public function reset(): never
    {
        Csrf::require();
        $token = (string) Request::input('token', '');
        $email = (string) Request::input('email', '');
        $password = (string) Request::post('password', '');
        $confirm = (string) Request::post('password_confirmation', '');
        $result = (new AccountService())->resetPassword($token, $email, $password, $confirm);
        if (!$result['ok']) {
            Session::set('_errors', $result['errors']);
            Response::redirect(url('/reset-password?token=' . urlencode($token) . '&email=' . urlencode($email)));
        }
        clear_input();
        flash('success', 'Your password has been updated. You can sign in now.');
        Response::redirect(url('/login'));
    }
}
