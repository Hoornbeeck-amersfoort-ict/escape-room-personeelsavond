<?php

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\User;
use App\View;

class AdminAuthController
{
    public function showLogin(): void
    {
        if (Auth::admin()) {
            header('Location: /admin/games');

            return;
        }

        echo View::render('admin/login');
    }

    public function login(): void
    {
        if (! Csrf::verify()) {
            http_response_code(419);
            echo 'Page Expired';

            return;
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $user = User::first(['email' => $email]);

        if (! $user || ! password_verify($password, $user['password'])) {
            View::flash('Onjuiste inloggegevens.');
            header('Location: /admin/login');

            return;
        }

        Auth::loginAdmin($user);
        header('Location: /admin/games');
    }

    public function logout(): void
    {
        if (Csrf::verify()) {
            Auth::logoutAdmin();
        }

        header('Location: /admin/login');
    }
}
