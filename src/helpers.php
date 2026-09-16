<?php

if (! function_exists('asset')) {
    /** Hangt de wijzigingstijd aan het pad, zodat browsers na een aanpassing
     * niet de oude versie uit hun cache blijven tonen. */
    function asset(string $path): string
    {
        $file = __DIR__.'/../public/'.ltrim($path, '/');

        return is_file($file) ? $path.'?v='.filemtime($file) : $path;
    }
}

if (! function_exists('class_basename')) {
    function class_basename(string $class): string
    {
        $parts = explode('\\', $class);

        return end($parts);
    }
}
