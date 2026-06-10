<?php
declare(strict_types=1);

final class Router
{
    /**
     * @param array<string, array<string, mixed>> $routes
     * @return array{key:string,page:array<string,mixed>,status:int}
     */
    public static function resolve(array $routes, string $requestUri, ?string $queryPage): array
    {
        $path = parse_url($requestUri, PHP_URL_PATH) ?: '/';
        $normalizedPath = '/' . trim($path, '/');
        $normalizedPath = $normalizedPath === '/' ? '/' : rtrim($normalizedPath, '/');

        if ($queryPage && isset($routes[$queryPage])) {
            return self::response($queryPage, $routes[$queryPage]);
        }

        foreach ($routes as $key => $route) {
            $routePath = (string) ($route['path'] ?? '');

            if ($routePath === $normalizedPath) {
                return self::response($key, $route);
            }
        }

        return self::response('not_found', $routes['not_found'], 404);
    }

    /**
     * @param array<string,mixed> $page
     * @return array{key:string,page:array<string,mixed>,status:int}
     */
    private static function response(string $key, array $page, int $status = 200): array
    {
        $page['key'] = $key;

        return [
            'key' => $key,
            'page' => $page,
            'status' => $status,
        ];
    }
}
