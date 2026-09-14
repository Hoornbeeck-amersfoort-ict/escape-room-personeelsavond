<?php

// A "framework" would use PSR-4. We are not a framework. We are having fun.
spl_autoload_register(function (string $class) {
    if (! str_starts_with($class, 'App\\')) {
        return;
    }

    $relative = substr($class, strlen('App\\'));
    $path = __DIR__.'/'.str_replace('\\', '/', $relative).'.php';

    if (is_file($path)) {
        require $path;

        return;
    }

    // Models.php holds several classes in one file (User, Game, Team, ...).
    if (is_file(__DIR__.'/Models.php')) {
        require_once __DIR__.'/Models.php';
    }
});
