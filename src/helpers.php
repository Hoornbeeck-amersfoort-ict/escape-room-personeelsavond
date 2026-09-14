<?php

if (! function_exists('class_basename')) {
    function class_basename(string $class): string
    {
        $parts = explode('\\', $class);

        return end($parts);
    }
}
