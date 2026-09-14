<?php

namespace App;

class View
{
    /** @param  array<string, mixed>  $data */
    public static function render(string $template, array $data = []): string
    {
        extract($data);
        ob_start();
        require __DIR__.'/../templates/'.$template.'.php';

        return ob_get_clean();
    }

    public static function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES);
    }

    public static function flash(string $message): void
    {
        $_SESSION['flash'] = $message;
    }

    public static function pullFlash(): ?string
    {
        $message = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        return $message;
    }

    /** "Team 10" before "Team 2" is wrong. Natural sort fixes it, like SORT_NATURAL in the Laravel version. */
    public static function sortByNatural(array $rows, string $key): array
    {
        usort($rows, fn ($a, $b) => strnatcasecmp($a[$key], $b[$key]));

        return $rows;
    }

    /** Renders an inner admin template and wraps it in the admin shell (sidebar, nav, flash). */
    public static function renderAdmin(string $template, array $data, string $title, ?array $game = null): string
    {
        $content = self::render($template, $data);

        return self::render('admin/_shell', ['title' => $title, 'game' => $game, 'content' => $content]);
    }
}
