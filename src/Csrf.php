<?php

namespace App;

class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="'.htmlspecialchars(self::token()).'">';
    }

    public static function verify(): bool
    {
        $submitted = $_POST['_token'] ?? '';

        return is_string($submitted) && hash_equals(self::token(), $submitted);
    }
}
