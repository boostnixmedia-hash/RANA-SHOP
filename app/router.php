<?php
declare(strict_types=1);

$GLOBALS['routes'] = [];
/** route('GET|POST', '/product/{slug}', fn($slug) => ...) */
function route(string $methods, string $pattern, callable $fn): void {
    $re = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $pattern) . '$#u';
    foreach (explode('|', $methods) as $m) $GLOBALS['routes'][] = [$m, $re, $fn];
}
function dispatch(string $path): void {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method === 'HEAD') $method = 'GET';
    $allowed = false;
    foreach ($GLOBALS['routes'] as [$m, $re, $fn]) {
        if (!preg_match($re, $path, $mm)) continue;
        if ($m !== $method) { $allowed = true; continue; }
        $args = array_filter($mm, 'is_string', ARRAY_FILTER_USE_KEY);
        if ($method === 'POST' && !str_starts_with($path, '/pay/')) verify_csrf();
        $fn(...array_values($args));
        return;
    }
    if ($allowed) { http_response_code(405); exit('Method not allowed'); }
    not_found();
}
