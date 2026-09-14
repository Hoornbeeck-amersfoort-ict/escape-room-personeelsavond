<?php

namespace App;

/**
 * A router in the loosest possible sense: an array of [method, pattern, handler].
 * {param} segments become named regex captures. No route caching, no
 * middleware pipeline, no service container — this is the joke, after all.
 */
class Router
{
    /** @var array<int, array{0: string, 1: string, 2: callable}> */
    private array $routes = [];

    public function get(string $pattern, callable $handler): void
    {
        $this->routes[] = ['GET', $pattern, $handler];
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->routes[] = ['POST', $pattern, $handler];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        $path = rtrim($path, '/');
        if ($path === '') {
            $path = '/';
        }

        foreach ($this->routes as [$routeMethod, $pattern, $handler]) {
            if ($routeMethod !== $method) {
                continue;
            }

            $params = $this->match($pattern, $path);
            if ($params !== null) {
                $handler(...$params);

                return;
            }
        }

        http_response_code(404);
        echo View::render('errors/404');
    }

    /** @return array<int, string>|null */
    private function match(string $pattern, string $path): ?array
    {
        $regex = preg_replace('#\{[a-zA-Z_]+\}#', '([^/]+)', $pattern);
        $regex = '#^'.$regex.'$#';

        if (preg_match($regex, $path, $matches)) {
            array_shift($matches);

            return $matches;
        }

        return null;
    }
}
